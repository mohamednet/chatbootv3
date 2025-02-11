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
            // First reminder: 3 hours before expiry (21 hours after creation)
            $trialsAboutToExpire = Trial::query()
                ->whereNotNull('assigned_user')
                ->where('created_at', '<=', now()->subHours(21))
                ->where('created_at', '>', now()->subHours(22))
                ->get();

            foreach ($trialsAboutToExpire as $trial) {
                $customer = Customer::where('facebook_id', $trial->assigned_user)->first();
                if ($customer && $customer->reminder_count_trial === 0) {
                    // Send Facebook message
                    $facebookService->sendMessage(
                        $trial->assigned_user, 
                        MessageTemplateService::getTrialTemplate('first', 'facebook')
                    );

                    // Send Email if available
                    if ($customer->email) {
                        Mail::to($customer->email)->send(new TrialReminder(
                            MessageTemplateService::getTrialTemplate('first', 'email_subject'),
                            MessageTemplateService::getTrialTemplate('first', 'email_content')
                        ));
                    }

                    $customer->increment('reminder_count_trial');
                    Log::info('Sent first trial reminder', ['customer_id' => $customer->id]);
                }
            }

            // Second reminder: Just expired (24 hours after creation)
            $justExpiredTrials = Trial::query()
                ->whereNotNull('assigned_user')
                ->where('created_at', '<=', now()->subHours(24))
                ->where('created_at', '>', now()->subHours(25))
                ->get();

            foreach ($justExpiredTrials as $trial) {
                $customer = Customer::where('facebook_id', $trial->assigned_user)->first();
                if ($customer && $customer->reminder_count_trial === 1) {
                    // Send Facebook message
                    $facebookService->sendMessage(
                        $trial->assigned_user, 
                        MessageTemplateService::getTrialTemplate('second', 'facebook')
                    );

                    // Send Email if available
                    if ($customer->email) {
                        Mail::to($customer->email)->send(new TrialReminder(
                            MessageTemplateService::getTrialTemplate('second', 'email_subject'),
                            MessageTemplateService::getTrialTemplate('second', 'email_content')
                        ));
                    }

                    $customer->increment('reminder_count_trial');
                    Log::info('Sent second trial reminder', ['customer_id' => $customer->id]);
                }
            }

            // Third reminder: 24 hours after expiry (48 hours after creation)
            $dayAfterExpiredTrials = Trial::query()
                ->whereNotNull('assigned_user')
                ->where('created_at', '<=', now()->subHours(48))
                ->where('created_at', '>', now()->subHours(49))
                ->get();

            foreach ($dayAfterExpiredTrials as $trial) {
                $customer = Customer::where('facebook_id', $trial->assigned_user)->first();
                if ($customer && $customer->reminder_count_trial === 2) {
                    // Send Facebook message
                    $facebookService->sendMessage(
                        $trial->assigned_user, 
                        MessageTemplateService::getTrialTemplate('third', 'facebook')
                    );

                    // Send Email if available
                    if ($customer->email) {
                        Mail::to($customer->email)->send(new TrialReminder(
                            MessageTemplateService::getTrialTemplate('third', 'email_subject'),
                            MessageTemplateService::getTrialTemplate('third', 'email_content')
                        ));
                    }

                    $customer->increment('reminder_count_trial');
                    Log::info('Sent third trial reminder', ['customer_id' => $customer->id]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error in trial reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
