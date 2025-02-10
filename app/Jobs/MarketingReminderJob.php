<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MarketingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(FacebookService $facebookService)
    {
        try {
            $customers = Customer::query()
                ->whereNull('trial_id')
                ->where('non_disturb', false)
                ->where('marketing_reminder_count', '<', 5)
                ->where(function ($query) {
                    $query->whereNull('last_marketing_message')
                          ->orWhere('last_marketing_message', '<=', now()->subHours(10));
                })
                ->get();

            foreach ($customers as $customer) {
                // Get appropriate message based on count
                $messageTemplate = match($customer->marketing_reminder_count) {
                    0 => 'First marketing message template',
                    1 => 'Second marketing message template',
                    2 => 'Third marketing message template',
                    3 => 'Fourth marketing message template',
                    4 => 'Fifth marketing message template',
                    default => null
                };

                if ($messageTemplate) {
                    // Send Facebook message
                    $facebookService->sendMessage($customer->facebook_id, $messageTemplate);

                    // Update customer
                    $customer->increment('marketing_reminder_count');
                    $customer->update(['last_marketing_message' => now()]);

                    // If this was the last message, set non_disturb
                    if ($customer->marketing_reminder_count >= 5) {
                        $customer->update(['non_disturb' => true]);
                    }

                    Log::info('Sent marketing reminder', [
                        'customer_id' => $customer->id,
                        'reminder_count' => $customer->marketing_reminder_count
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in marketing reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
