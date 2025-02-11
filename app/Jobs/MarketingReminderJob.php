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
            Log::info('Starting marketing reminder job');

            // Base query for all no-trial customers
            $baseQuery = Customer::query()
                ->where('trial_status', 'Not Sent')
                ->where(function($query) {
                    $query->whereNull('paid_status')
                          ->orWhere('paid_status', false);
                });

            // 1. First message: 5+ hours after last message
            $firstMessageCustomers = (clone $baseQuery)
                ->where('marketing_message_count', 0)
                ->where(function($query) {
                    $query->whereNull('last_message_at')
                          ->orWhere('last_message_at', '<=', now()->subHours(5));
                })
                ->get();

            foreach ($firstMessageCustomers as $customer) {
                try {
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        MessageTemplateService::getMarketingTemplate(1)
                    );

                    $customer->update([
                        'marketing_message_count' => 1,
                        'last_marketing_message' => now()
                    ]);

                    Log::info('Sent first marketing message', [
                        'customer_id' => $customer->facebook_id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending first marketing message', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 2. Second message: 2+ days after last message
            $secondMessageCustomers = (clone $baseQuery)
                ->where('marketing_message_count', 1)
                ->where('last_message_at', '<=', now()->subDays(2))
                ->get();

            foreach ($secondMessageCustomers as $customer) {
                try {
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        MessageTemplateService::getMarketingTemplate(2)
                    );

                    $customer->update([
                        'marketing_message_count' => 2,
                        'last_marketing_message' => now()
                    ]);

                    Log::info('Sent second marketing message', [
                        'customer_id' => $customer->facebook_id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending second marketing message', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 3. Third message: 4+ days after last message
            $thirdMessageCustomers = (clone $baseQuery)
                ->where('marketing_message_count', 2)
                ->where('last_message_at', '<=', now()->subDays(4))
                ->get();

            foreach ($thirdMessageCustomers as $customer) {
                try {
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        MessageTemplateService::getMarketingTemplate(3)
                    );

                    $customer->update([
                        'marketing_message_count' => 3,
                        'last_marketing_message' => now()
                    ]);

                    Log::info('Sent third marketing message', [
                        'customer_id' => $customer->facebook_id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending third marketing message', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 4. Fourth message: 5+ days after last message
            $fourthMessageCustomers = (clone $baseQuery)
                ->where('marketing_message_count', 3)
                ->where('last_message_at', '<=', now()->subDays(5))
                ->get();

            foreach ($fourthMessageCustomers as $customer) {
                try {
                    $facebookService->sendMessage(
                        $customer->facebook_id,
                        MessageTemplateService::getMarketingTemplate(4)
                    );

                    $customer->update([
                        'marketing_message_count' => 4,
                        'last_marketing_message' => now()
                    ]);

                    Log::info('Sent fourth marketing message', [
                        'customer_id' => $customer->facebook_id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending fourth marketing message', [
                        'customer_id' => $customer->facebook_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Marketing reminder job completed successfully');
        } catch (\Exception $e) {
            Log::error('Error in marketing reminder job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
