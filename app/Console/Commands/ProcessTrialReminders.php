<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Services\FacebookService;
use App\Services\MessageTemplateService;
use Illuminate\Support\Facades\Log;

class ProcessTrialReminders extends Command
{
    protected $signature = 'trials:process-reminders';
    protected $description = 'Process trial reminders every 5 minutes';

    public function handle(FacebookService $facebookService)
    {
        Log::info('Trial reminder process running at: ' . now());

        try {
            // First reminder: 3 hours or less before trial expiry
            $firstReminderCustomers = Customer::query()
                ->join('trials', 'trials.assigned_user', '=', 'customers.facebook_id')
                ->where('trials.created_at', '<=', now()->subHours(21))
                ->where('trials.created_at', '>', now()->subHours(24))
                ->where('customers.reminder_count_trial', 0)
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            Log::info('Found customers for first reminder:', ['count' => $firstReminderCustomers->count()]);

            foreach ($firstReminderCustomers as $customer) {
                try {
                    // Send Facebook message
                    $facebookMessage = "REMINDER:\n" . MessageTemplateService::getTrialTemplate('first', 'facebook');
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        $facebookMessage
                    );

                    // Update reminder count
                    $customer->reminder_count_trial = 1;
                    $customer->save();

                    Log::info('First reminder sent successfully', [
                        'customer_id' => $customer->id,
                        'facebook_id' => $customer->facebook_id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending first reminder', [
                        'customer_id' => $customer->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Second reminder: 1-24 hours after trial expiry
            $secondReminderCustomers = Customer::query()
                ->join('trials', 'trials.assigned_user', '=', 'customers.facebook_id')
                ->where('trials.created_at', '<=', now()->subHours(48))
                ->where('trials.created_at', '>', now()->subHours(72))
                ->where('customers.reminder_count_trial', 1)
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            Log::info('Found customers for second reminder:', ['count' => $secondReminderCustomers->count()]);

            foreach ($secondReminderCustomers as $customer) {
                try {
                    // Send Facebook message
                    $facebookMessage = "REMINDER:\n" . MessageTemplateService::getTrialTemplate('second', 'facebook');
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        $facebookMessage
                    );

                    // Update reminder count
                    $customer->reminder_count_trial = 2;
                    $customer->save();

                    Log::info('Second reminder sent successfully', [
                        'customer_id' => $customer->id,
                        'facebook_id' => $customer->facebook_id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending second reminder', [
                        'customer_id' => $customer->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Third reminder: 2+ days after trial expiry
            $thirdReminderCustomers = Customer::query()
                ->join('trials', 'trials.assigned_user', '=', 'customers.facebook_id')
                ->where('trials.created_at', '<=', now()->subDays(3))
                ->whereIn('customers.reminder_count_trial', [0, 2])
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            Log::info('Found customers for third reminder:', ['count' => $thirdReminderCustomers->count()]);

            foreach ($thirdReminderCustomers as $customer) {
                try {
                    // Send Facebook message
                    $facebookMessage = "REMINDER:\n" . MessageTemplateService::getTrialTemplate('third', 'facebook');
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        $facebookMessage
                    );

                    // Update reminder count
                    $customer->reminder_count_trial = 3;
                    $customer->save();

                    Log::info('Third reminder sent successfully', [
                        'customer_id' => $customer->id,
                        'facebook_id' => $customer->facebook_id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending third reminder', [
                        'customer_id' => $customer->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Trial reminder process completed', [
                'first_reminders' => $firstReminderCustomers->count(),
                'second_reminders' => $secondReminderCustomers->count(),
                'third_reminders' => $thirdReminderCustomers->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error in trial reminder process: ' . $e->getMessage());
        }
    }
}
