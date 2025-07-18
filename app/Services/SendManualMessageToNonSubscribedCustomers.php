<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendManualMessageToNonSubscribedCustomers
{
    private $pageAccessToken;
    private const SPECIAL_OFFER_MESSAGE = "🎆 4th of July Special! 
        Start today for just $8.99 — includes 1st month + IBO Player activation
        Then only $13.95/month

        We offer a premium service with:
        📺 30,000+ live channels
        🎬 100,000+ movies & series in 4K/8K
        🏈 NFL, NBA, NHL, MLB, ESPN, PPV & more
        🎞 Netflix, Hulu, Prime, HBO, Paramount+
        🌍 USA local + worldwide channels

        ✅ Works on all devices
        ✅ Instant setup — no contracts
        ✅ 7-day money-back guarantee

        🎉 Message me to get started today!";

    public function __construct()
    {
        $this->pageAccessToken = config('services.facebook.page_access_token');
    }

    public function execute(string $message = self::SPECIAL_OFFER_MESSAGE)
    {
        try {
            $customers = Customer::where(function($query) {
                $query->where('paid_status', false)
                      ->orWhereNull('paid_status');
            })
            ->whereNotNull('facebook_id')
            ->where('facebook_messages_disabled', false)  // Skip customers who have blocked messages
            ->get();

            $successCount = 0;
            $failCount = 0;

            foreach ($customers as $customer) {
                try {
                    $response = Http::post("https://graph.facebook.com/v18.0/me/messages", [
                        'access_token' => $this->pageAccessToken,
                        'recipient' => [
                            'id' => $customer->facebook_id
                        ],
                        'message' => [
                            'text' => $message
                        ],
                        'messaging_type' => 'MESSAGE_TAG',
                        'tag' => 'post_purchase_update'  // This tag allows sending messages outside 24h window
                    ]);

                    if ($response->successful()) {
                        $successCount++;
                        Log::info("Message sent successfully to customer ID: {$customer->facebook_id}");
                    } else {
                        $error = $response->json();
                        
                        // Handle specific Facebook error codes
                        if (isset($error['error']['code'])) {
                            switch ($error['error']['code']) {
                                case 10:
                                    if (isset($error['error']['error_subcode']) && $error['error']['error_subcode'] === 2018278) {
                                        Log::warning("Message outside allowed time window for customer ID: {$customer->facebook_id}");
                                    }
                                    break;
                                case 551:
                                    if (isset($error['error']['error_subcode']) && $error['error']['error_subcode'] === 1545041) {
                                        // Update customer status to indicate they've blocked messages
                                        $customer->facebook_messages_disabled = true;
                                        $customer->facebook_disabled_at = now();
                                        $customer->facebook_disabled_reason = 'User blocked messages or deactivated account';
                                        $customer->save();
                                        Log::warning("Customer has blocked messages or deactivated account: {$customer->facebook_id}");
                                    }
                                    break;
                                default:
                                    Log::error("Facebook API error for customer ID: {$customer->facebook_id}. Error: " . json_encode($error));
                            }
                        }
                        
                        $failCount++;
                    }

                    // Add a small delay to avoid rate limiting
                    usleep(250000); // 250ms delay
                } catch (\Exception $e) {
                    $failCount++;
                    Log::error("Error sending message to customer ID: {$customer->facebook_id}. Error: " . $e->getMessage());
                }
            }

            return [
                'total_customers' => $customers->count(),
                'success_count' => $successCount,
                'fail_count' => $failCount
            ];
        } catch (\Exception $e) {
            Log::error("Error in SendManualMessageToNonSubscribedCustomers: " . $e->getMessage());
            throw $e;
        }
    }
}
