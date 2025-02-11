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

class TrialReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle(FacebookService $facebookService)
    {
        try {
            Log::info('Starting trial reminder job - checking all conditions');

            // Check if Facebook service is available
            if (!$facebookService) {
                Log::error('Facebook service not available');
                throw new \Exception('Facebook service not available');
            }

            // 1. Handle old customers with expired trials (7+ days)
            $oldExpiredQuery = Customer::query()
                ->join('trials', 'trials.assigned_user', '=', 'customers.facebook_id')
                ->where('customers.trial_status', 'Sent')
                ->where(function($query) {
                    $query->whereNull('customers.paid_status')
                          ->orWhere('customers.paid_status', false);
                })
                ->where('trials.created_at', '<=', now()->subDays(7))
                ->where(function($query) {
                    $query->whereNull('customers.reminder_count_trial')
                          ->orWhere('customers.reminder_count_trial', 0);
                })
                ->where(function($query) {
                    $query->whereNull('customers.last_reminder_sent')
                          ->orWhere('customers.last_reminder_sent', '<=', now()->subHours(3));
                })
                ->select('customers.*', 'trials.created_at as trial_created_at');

            Log::info('Old expired trials query:', [
                'sql' => $oldExpiredQuery->toSql(),
                'bindings' => $oldExpiredQuery->getBindings()
            ]);

            $oldExpiredCustomers = $oldExpiredQuery->get();

            Log::info('Found old expired trial customers:', [
                'count' => $oldExpiredCustomers->count(),
                'customer_ids' => $oldExpiredCustomers->pluck('facebook_id')->toArray()
            ]);

            foreach ($oldExpiredCustomers as $customer) {
                try {
                    Log::info('Processing old expired trial customer:', [
                        'customer_id' => $customer->facebook_id,
                        'trial_created_at' => $customer->trial_created_at,
                        'reminder_count' => $customer->reminder_count_trial
                    ]);

                    // Send Facebook message
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        MessageTemplateService::getTrialTemplate('third', 'facebook')
                    );

                    // Send email
                    if ($customer->email) {
                        Mail::to($customer->email)->send(new TrialReminder(
                            MessageTemplateService::getTrialTemplate('third', 'email_subject'),
                            MessageTemplateService::getTrialTemplate('third', 'email_content')
                        ));
                    }

                    $customer->update([
                        'reminder_count_trial' => 3,
                        'last_reminder_sent' => now()
                    ]);

                    Log::info('Sent third message to old expired trial customer', [
                        'customer_id' => $customer->facebook_id,
                        'trial_created' => $customer->trial_created_at
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending third message to old customer', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 2. Handle customers with 3 hours left in trial (21 hours old)
            $customersWithThreeHoursQuery = Customer::query()
                ->join('trials', 'trials.assigned_user', '=', 'customers.facebook_id')
                ->where('customers.trial_status', 'Sent')
                ->where(function($query) {
                    $query->whereNull('customers.paid_status')
                          ->orWhere('customers.paid_status', false);
                })
                ->where('customers.reminder_count_trial', 0)
                ->where('trials.created_at', '<=', now()->subHours(3))
                ->where('trials.created_at', '>', now()->subHours(4))
                ->where(function($query) {
                    $query->whereNull('customers.last_reminder_sent')
                          ->orWhere('customers.last_reminder_sent', '<=', now()->subHours(3));
                })
                ->select('customers.*', 'trials.created_at as trial_created_at');

            Log::info('Customers with 3 hours left query:', [
                'sql' => $customersWithThreeHoursQuery->toSql(),
                'bindings' => $customersWithThreeHoursQuery->getBindings()
            ]);

            $customersWithThreeHours = $customersWithThreeHoursQuery->get();

            Log::info('Found customers with 3 hours left:', [
                'count' => $customersWithThreeHours->count(),
                'customer_ids' => $customersWithThreeHours->pluck('facebook_id')->toArray()
            ]);

            foreach ($customersWithThreeHours as $customer) {
                try {
                    Log::info('Processing customer with 3 hours left:', [
                        'customer_id' => $customer->facebook_id,
                        'trial_created_at' => $customer->trial_created_at,
                        'reminder_count' => $customer->reminder_count_trial
                    ]);

                    // Send Facebook message
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        MessageTemplateService::getTrialTemplate('first', 'facebook')
                    );

                    // Send email
                    if ($customer->email) {
                        Mail::to($customer->email)->send(new TrialReminder(
                            MessageTemplateService::getTrialTemplate('first', 'email_subject'),
                            MessageTemplateService::getTrialTemplate('first', 'email_content')
                        ));
                    }

                    $customer->update([
                        'reminder_count_trial' => 1,
                        'last_reminder_sent' => now()
                    ]);

                    Log::info('Sent first message to customer with 3 hours left', [
                        'customer_id' => $customer->facebook_id,
                        'trial_created' => $customer->trial_created_at
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending first message', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 3. Handle expired trials (24h-7d)
            $expiredTrialsQuery = Customer::query()
                ->join('trials', 'trials.assigned_user', '=', 'customers.facebook_id')
                ->where('customers.trial_status', 'Sent')
                ->where(function($query) {
                    $query->whereNull('customers.paid_status')
                          ->orWhere('customers.paid_status', false);
                })
                ->where('trials.created_at', '<=', now()->subDays(1))
                ->where('trials.created_at', '>', now()->subDays(7))
                ->where(function($query) {
                    $query->whereNull('customers.last_reminder_sent')
                          ->orWhere('customers.last_reminder_sent', '<=', now()->subHours(3));
                })
                ->select('customers.*', 'trials.created_at as trial_created_at');

            Log::info('Expired trials query:', [
                'sql' => $expiredTrialsQuery->toSql(),
                'bindings' => $expiredTrialsQuery->getBindings()
            ]);

            $expiredTrials = $expiredTrialsQuery->get();

            Log::info('Found expired trials:', [
                'count' => $expiredTrials->count(),
                'customer_ids' => $expiredTrials->pluck('facebook_id')->toArray()
            ]);

            foreach ($expiredTrials as $customer) {
                try {
                    Log::info('Processing expired trial customer:', [
                        'customer_id' => $customer->facebook_id,
                        'trial_created_at' => $customer->trial_created_at,
                        'reminder_count' => $customer->reminder_count_trial
                    ]);

                    // Send Facebook message
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        MessageTemplateService::getTrialTemplate('second', 'facebook')
                    );

                    // Send email
                    if ($customer->email) {
                        Mail::to($customer->email)->send(new TrialReminder(
                            MessageTemplateService::getTrialTemplate('second', 'email_subject'),
                            MessageTemplateService::getTrialTemplate('second', 'email_content')
                        ));
                    }

                    $customer->update([
                        'reminder_count_trial' => 2,
                        'last_reminder_sent' => now()
                    ]);

                    Log::info('Sent second message to expired trial customer', [
                        'customer_id' => $customer->facebook_id,
                        'trial_created' => $customer->trial_created_at
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending second message', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Trial reminder job completed successfully');
        } catch (\Exception $e) {
            Log::error('Error in TrialReminderJob: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
