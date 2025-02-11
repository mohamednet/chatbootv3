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
            Log::info('Starting trial reminder job');

            // Get all trial customers who haven't received a reminder
            $customers = Customer::query()
                ->join('trials', 'trials.assigned_user', '=', 'customers.facebook_id')
                ->where('customers.trial_status', 'Sent')
                ->where('customers.reminder_count_trial', 0)
                ->select('customers.*', 'trials.created_at as trial_created_at')
                ->get();

            Log::info('Found customers:', ['count' => $customers->count()]);

            foreach ($customers as $customer) {
                try {
                    // Send Facebook message
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        "Your trial will expire soon! Subscribe now to keep enjoying our service. Contact us for more information."
                    );

                    // Update reminder count
                    $customer->reminder_count_trial = 1;
                    $customer->last_reminder_sent = now();
                    $customer->save();

                    Log::info('Sent reminder to customer', ['facebook_id' => $customer->facebook_id]);
                } catch (\Exception $e) {
                    Log::error('Error sending reminder to customer', [
                        'facebook_id' => $customer->facebook_id,
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
