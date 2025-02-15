<?php

namespace App\Jobs;

use App\Mail\TrialReminder;
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
use Carbon\Carbon;

class TrialReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle(FacebookService $facebookService)
    {
        try {
            Log::info('Starting trial reminder job at: ' . now());

            // First reminder: 3 hours or less before trial expiry
            $firstReminderCustomers = Customer::query()
                ->join('trials', 'trials.assigned_user', '=', 'customers.facebook_id')
                ->where('customers.trial_status', 'Sent')
                ->where(function($query) {
                    $query->whereNull('customers.paid_status')
                          ->orWhere('customers.paid_status', false);
                })
                ->where('customers.reminder_count_trial', 0)
                ->where('trials.created_at', '<=', now()->subHours(21))
                ->where('trials.created_at', '>', now()->subHours(24))
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            Log::info('Found first reminder customers:', ['count' => $firstReminderCustomers->count()]);

            foreach ($firstReminderCustomers as $customer) {
                try {
                    // Send Facebook message
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        MessageTemplateService::getTrialTemplate('first', 'facebook')
                    );

                    // Send email if available
                    if ($customer->email) {
                        Mail::to($customer->email)->send(new TrialReminder(
                            MessageTemplateService::getTrialTemplate('first', 'email_subject'),
                            MessageTemplateService::getTrialTemplate('first', 'email_content')
                        ));
                    }

                    // Update reminder count
                    $customer->reminder_count_trial = 1;
                    $customer->save();

                    Log::info('Sent first reminder', ['customer_id' => $customer->facebook_id]);
                } catch (\Exception $e) {
                    Log::error('Error sending first reminder', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Second reminder: between 1h and 24h after trial expiry
            $secondReminderCustomers = Customer::query()
                ->join('trials', 'trials.assigned_user', '=', 'customers.facebook_id')
                ->where('customers.trial_status', 'Sent')
                ->where(function($query) {
                    $query->whereNull('customers.paid_status')
                          ->orWhere('customers.paid_status', false);
                })
                ->where('customers.reminder_count_trial', 1)
                ->where('trials.created_at', '<=', now()->subHours(25))
                ->where('trials.created_at', '>', now()->subHours(48))
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            Log::info('Found second reminder customers:', ['count' => $secondReminderCustomers->count()]);

            foreach ($secondReminderCustomers as $customer) {
                try {
                    // Send Facebook message
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        MessageTemplateService::getTrialTemplate('second', 'facebook')
                    );

                    // Send email if available
                    if ($customer->email) {
                        Mail::to($customer->email)->send(new TrialReminder(
                            MessageTemplateService::getTrialTemplate('second', 'email_subject'),
                            MessageTemplateService::getTrialTemplate('second', 'email_content')
                        ));
                    }

                    // Update reminder count
                    $customer->reminder_count_trial = 2;
                    $customer->save();

                    Log::info('Sent second reminder', ['customer_id' => $customer->facebook_id]);
                } catch (\Exception $e) {
                    Log::error('Error sending second reminder', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Third reminder: 2+ days after trial expiry OR never reminded
            $thirdReminderCustomers = Customer::query()
                ->join('trials', 'trials.assigned_user', '=', 'customers.facebook_id')
                ->where('trials.created_at', '<', now()->subHours(32))
                ->where('customers.trial_status', 'Sent')
                ->where(function($query) {
                    $query->whereNull('customers.paid_status')
                          ->orWhere('customers.paid_status', false);
                })
                ->where(function($query) {
                    $query->where('customers.reminder_count_trial', 2)
                          ->orWhere('customers.reminder_count_trial', 0);
                })
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            Log::info('Found third reminder customers:', ['count' => $thirdReminderCustomers->count()]);

            foreach ($thirdReminderCustomers as $customer) {
                try {
                    $fbMessageSent = false;
                    $emailSent = false;
                    
                    // Try to send Facebook message
                    try {
                        $fbMessageSent = $facebookService->sendMessage(
                            $customer->facebook_id,
                            MessageTemplateService::getTrialTemplate('third', 'facebook')
                        );
                    } catch (\Exception $e) {
                        Log::error('Error sending Facebook message in third reminder', [
                            'customer_id' => $customer->facebook_id,
                            'error' => $e->getMessage()
                        ]);
                    }

                    // Try to send email if available
                    if ($customer->email) {
                        try {
                            Mail::to($customer->email)->send(new TrialReminder(
                                MessageTemplateService::getTrialTemplate('third', 'email_subject'),
                                MessageTemplateService::getTrialTemplate('third', 'email_content')
                            ));
                            $emailSent = true;
                        } catch (\Exception $e) {
                            Log::error('Error sending email in third reminder', [
                                'customer_id' => $customer->facebook_id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    // Update reminder count to 3 only if both messages were sent successfully
                    if ($fbMessageSent && $emailSent) {
                        $customer->reminder_count_trial = 3;
                        $customer->save();
                        Log::info('Sent third reminder (both FB and email)', ['customer_id' => $customer->facebook_id]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error in third reminder process', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Trial reminder job completed');
        } catch (\Exception $e) {
            Log::error('Error in trial reminder job: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
