<?php

namespace App\Jobs;

use App\Models\Trial;
use App\Models\Customer;
use App\Services\FacebookService;
use App\Services\MessageTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrialReminder;

class TrialReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(FacebookService $facebookService)
    {
        try {
            // Handle customers who had trials but haven't paid
            $customersWithTrials = Customer::query()
                ->where('trial_status', 'Sent')  // Has received a trial
                ->where(function($query) {
                    $query->whereNull('paid_status')
                          ->orWhere('paid_status', false);
                })
                ->where('reminder_count_trial', '<', 3)
                ->where(function($query) {
                    $query->whereNull('last_reminder_sent')
                        ->orWhere(function($q) {
                            $q->where('reminder_count_trial', 0); // First: Immediate
                        })
                        ->orWhere(function($q) {
                            $q->where('reminder_count_trial', 1)
                              ->where('last_reminder_sent', '<=', now()->subDays(1)); // Second: 24h delay
                        })
                        ->orWhere(function($q) {
                            $q->where('reminder_count_trial', 2)
                              ->where('last_reminder_sent', '<=', now()->subDays(2)); // Third: 48h delay
                        });
                })
                ->get();

            foreach ($customersWithTrials as $customer) {
                $reminderType = match($customer->reminder_count_trial) {
                    0 => 'first',
                    1 => 'second',
                    2 => 'third'
                };

                // Send messages
                $facebookService->sendMessage(
                    $customer->facebook_id,
                    MessageTemplateService::getTrialTemplate($reminderType, 'facebook')
                );

                if ($customer->email) {
                    Mail::to($customer->email)->send(new TrialReminder(
                        MessageTemplateService::getTrialTemplate($reminderType, 'email_subject'),
                        MessageTemplateService::getTrialTemplate($reminderType, 'email_content')
                    ));
                }

                $customer->increment('reminder_count_trial');
                $customer->update(['last_reminder_sent' => now()]);
                Log::info("Sent {$reminderType} trial reminder", ['customer_id' => $customer->id]);
            }

            // Handle customers who never had trials
            $customersWithoutTrials = Customer::query()
                ->where('trial_status', 'Not Sent')  // Never had a trial
                ->where(function($query) {
                    $query->whereNull('paid_status')
                          ->orWhere('paid_status', false);
                })
                ->where('reminder_count_trial', '<', 5)
                ->where(function($query) {
                    $query->whereNull('last_reminder_sent')
                        ->orWhere(function($q) {
                            $q->where('reminder_count_trial', 0); // First: Immediate
                        })
                        ->orWhere(function($q) {
                            $q->where('reminder_count_trial', 1)
                              ->where('last_reminder_sent', '<=', now()->subDays(1)); // Second: 24h delay
                        })
                        ->orWhere(function($q) {
                            $q->where('reminder_count_trial', 2)
                              ->where('last_reminder_sent', '<=', now()->subDays(2)); // Third: 48h delay
                        })
                        ->orWhere(function($q) {
                            $q->where('reminder_count_trial', 3)
                              ->where('last_reminder_sent', '<=', now()->subDays(3)); // Fourth: 72h delay
                        })
                        ->orWhere(function($q) {
                            $q->where('reminder_count_trial', 4)
                              ->where('last_reminder_sent', '<=', now()->subDays(4)); // Fifth: 96h delay
                        });
                })
                ->get();

            foreach ($customersWithoutTrials as $customer) {
                $reminderType = match($customer->reminder_count_trial) {
                    0 => 'first',
                    1 => 'second',
                    2 => 'third',
                    3 => 'fourth',
                    4 => 'fifth'
                };

                // Send messages
                $facebookService->sendMessage(
                    $customer->facebook_id,
                    MessageTemplateService::getTrialTemplate($reminderType . '_no_trial', 'facebook')
                );

                if ($customer->email) {
                    Mail::to($customer->email)->send(new TrialReminder(
                        MessageTemplateService::getTrialTemplate($reminderType . '_no_trial', 'email_subject'),
                        MessageTemplateService::getTrialTemplate($reminderType . '_no_trial', 'email_content')
                    ));
                }

                $customer->increment('reminder_count_trial');
                $customer->update(['last_reminder_sent' => now()]);
                Log::info("Sent {$reminderType} no-trial reminder", ['customer_id' => $customer->id]);
            }

            Log::info('Trial reminder job completed successfully');
        } catch (\Exception $e) {
            Log::error('Error in trial reminder job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
