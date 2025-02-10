<?php

namespace App\Jobs;

use App\Models\Trial;
use App\Models\Customer;
use App\Services\FacebookService;
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
        //
    }

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
                    // TODO: Add message template
                    $facebookService->sendMessage($trial->assigned_user, 'First reminder template');

                    // Send Email if available
                    if ($customer->email) {
                        // TODO: Add email template
                        Mail::to($customer->email)->send('First email template');
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
                    // TODO: Add message template
                    $facebookService->sendMessage($trial->assigned_user, 'Second reminder template');

                    // Send Email if available
                    if ($customer->email) {
                        // TODO: Add email template
                        Mail::to($customer->email)->send('Second email template');
                    }

                    $customer->increment('reminder_count_trial');
                    Log::info('Sent second trial reminder', ['customer_id' => $customer->id]);
                }
            }

            // Third reminder: Timing to be defined
            // TODO: Implement third reminder logic when timing is provided

        } catch (\Exception $e) {
            Log::error('Error in trial reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
