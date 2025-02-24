<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Services\FacebookService;
use App\Services\MessageTemplateService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrialReminder;

class ProcessTrialReminders extends Command
{
    protected $signature = 'trials:process-reminders';
    protected $description = 'Process trial reminders every 5 minutes';

    public function handle(FacebookService $facebookService)
    {
        $startTime = microtime(true);
        Log::channel('trial-reminders')->info('Trial reminder process running at: ' . now());

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
                ->where('customers.facebook_messages_disabled', false)  // Skip customers who have blocked messages
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            Log::channel('trial-reminders')->info('First reminder customers found:', ['count' => $firstReminderCustomers->count()]);

            foreach ($firstReminderCustomers as $customer) {
                try {
                    Log::channel('trial-reminders')->info('Sending first reminder to customer:', [
                        'customer_id' => $customer->id,
                        'facebook_id' => $customer->facebook_id,
                        'trial_end' => $customer->trial_created_at
                    ]);

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

                    Log::channel('trial-reminders')->info('Sent first reminder', ['customer_id' => $customer->facebook_id]);
                } catch (\Exception $e) {
                    Log::channel('trial-reminders')->error('Error sending first reminder', [
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
                ->where('customers.facebook_messages_disabled', false)  // Skip customers who have blocked messages
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            Log::channel('trial-reminders')->info('Second reminder customers found:', ['count' => $secondReminderCustomers->count()]);

            foreach ($secondReminderCustomers as $customer) {
                try {
                    Log::channel('trial-reminders')->info('Sending second reminder to customer:', [
                        'customer_id' => $customer->id,
                        'facebook_id' => $customer->facebook_id,
                        'trial_end' => $customer->trial_created_at
                    ]);

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

                    Log::channel('trial-reminders')->info('Sent second reminder', ['customer_id' => $customer->facebook_id]);
                } catch (\Exception $e) {
                    Log::channel('trial-reminders')->error('Error sending second reminder', [
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

            Log::channel('trial-reminders')->info('Third reminder customers found:', ['count' => $thirdReminderCustomers->count()]);

            foreach ($thirdReminderCustomers as $customer) {
                try {
                    Log::channel('trial-reminders')->info('Sending third reminder to customer:', [
                        'customer_id' => $customer->id,
                        'facebook_id' => $customer->facebook_id,
                        'trial_end' => $customer->trial_created_at
                    ]);

                    $fbMessageSent = false;
                    $emailSent = false;
                    
                    // Try to send Facebook message
                    try {
                        $facebookMessage = "REMINDER:\n" . MessageTemplateService::getTrialTemplate('third', 'facebook');
                        $fbMessageSent = $facebookService->sendMessage(
                            $customer->facebook_id,
                            $facebookMessage
                        );
                    } catch (\Exception $e) {
                        Log::channel('trial-reminders')->error('Error sending Facebook message in third reminder', [
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
                            Log::channel('trial-reminders')->error('Error sending email in third reminder', [
                                'customer_id' => $customer->facebook_id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    // Update reminder count to 3 only if both messages were sent successfully
                    if ($fbMessageSent && $emailSent) {
                        $customer->reminder_count_trial = 3;
                        $customer->save();
                        Log::channel('trial-reminders')->info('Sent third reminder (both FB and email)', ['customer_id' => $customer->facebook_id]);
                    }
                } catch (\Exception $e) {
                    Log::channel('trial-reminders')->error('Error in third reminder process', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Add monitoring metrics at the end
            if (app()->environment('production')) {
                $metrics = [
                    'first_reminders' => $firstReminderCustomers->count(),
                    'second_reminders' => $secondReminderCustomers->count(),
                    'third_reminders' => $thirdReminderCustomers->count(),
                    'execution_time' => microtime(true) - $startTime,
                    'memory_usage' => memory_get_peak_usage(true) / 1024 / 1024 . 'MB'
                ];
                
                Log::channel('trial-reminders')->info('Trial reminders metrics', $metrics);
                
                if ($metrics['execution_time'] > 30) {
                    Log::channel('slack')->warning('Trial reminder process took longer than expected', $metrics);
                }
            }
        } catch (\Exception $e) {
            if (app()->environment('production')) {
                Log::channel('slack')->error('Trial reminder process failed: ' . $e->getMessage());
            }
            throw $e;
        }
    }
}
