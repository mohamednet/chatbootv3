<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Customer;
use App\Services\FacebookService;
use App\Services\MessageTemplateService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrialReminder;

class ProcessTrialRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle(FacebookService $facebookService)
    {
        $startTime = microtime(true);
        Log::info('Trial reminder process running at: ' . now());

        try {
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
                ->where('customers.facebook_messages_disabled', '=', 0)
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            foreach ($firstReminderCustomers as $customer) {
                try {
                    // Send Facebook message
                    $facebookMessage = "REMINDER:\n" . MessageTemplateService::getTrialTemplate('first', 'facebook');
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        $facebookMessage
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
                ->where('customers.facebook_messages_disabled', '=', 0)
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            foreach ($secondReminderCustomers as $customer) {
                try {
                    // Send Facebook message
                    $facebookMessage = "REMINDER:\n" . MessageTemplateService::getTrialTemplate('second', 'facebook');
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        $facebookMessage
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
                ->where('customers.facebook_messages_disabled', '=', 0)
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            foreach ($thirdReminderCustomers as $customer) {
                try {
                    // Send Facebook message
                    $facebookMessage = "REMINDER:\n" . MessageTemplateService::getTrialTemplate('third', 'facebook');
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        $facebookMessage
                    );

                    // Send email if available
                    if ($customer->email) {
                        Mail::to($customer->email)->send(new TrialReminder(
                            MessageTemplateService::getTrialTemplate('third', 'email_subject'),
                            MessageTemplateService::getTrialTemplate('third', 'email_content')
                        ));
                    }

                    // Update reminder count
                    $customer->reminder_count_trial = 3;
                    $customer->save();

                    Log::info('Sent third reminder', ['customer_id' => $customer->facebook_id]);
                } catch (\Exception $e) {
                    Log::error('Error in third reminder process', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log metrics
            $metrics = [
                'first_reminders' => $firstReminderCustomers->count(),
                'second_reminders' => $secondReminderCustomers->count(),
                'third_reminders' => $thirdReminderCustomers->count(),
                'execution_time' => microtime(true) - $startTime,
                'memory_usage' => memory_get_peak_usage(true) / 1024 / 1024 . 'MB'
            ];
            
            Log::info('Trial reminders metrics', $metrics);
            
            if ($metrics['execution_time'] > 30) {
                Log::warning('Trial reminder process took longer than expected', $metrics);
            }

        } catch (\Exception $e) {
            Log::error('Error in trial reminder process: ' . $e->getMessage());
            throw $e;
        }
    }
}
