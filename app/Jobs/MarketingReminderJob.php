<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\FacebookService;
use App\Services\MessageTemplateService;
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

    public function handle(FacebookService $facebookService, MessageTemplateService $messageTemplateService)
    {
        try {
            $customers = Customer::query()
                ->whereNull('trial_id')
                ->where('non_disturb', false)
                ->where('marketing_reminder_count', '<', 5)
                ->where(function ($query) {
                    $query->whereNull('last_marketing_message')
                          ->orWhere('last_marketing_message', '<=', now()->subDays(3));
                })
                ->get();

            foreach ($customers as $customer) {
                // Get message based on count (1-based for templates)
                $messageTemplate = $messageTemplateService->getMarketingTemplate(
                    $customer->marketing_reminder_count + 1
                );

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

                    Log::info('Sent marketing message', [
                        'customer_id' => $customer->id,
                        'message_number' => $customer->marketing_reminder_count
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
