<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendManualMessageToExpiredSubscribers
{
    private $pageAccessToken;

    private const DEFAULT_MESSAGE =  "
 🔥 Are you interested in a subscription?
    34,000+ Channels & 140,000+ Movies/Series — only $13.95/month!
    💰 Full refund within 7 days if not satisfied
    ";

    public function __construct()
    {
        $this->pageAccessToken = config('services.facebook.page_access_token');
    }

    /**
     * Send a manual message to customers whose subscription has expired.
     *
     * @param string $message Custom message to send; defaults to DEFAULT_MESSAGE
     * @return array Summary counts
     */
    public function execute(string $message = self::DEFAULT_MESSAGE)
    {
        try {
            $customers = Customer::query()
                ->whereNotNull('subscription_end_date')
                ->where('subscription_end_date', '<=', now())
                ->whereNotNull('facebook_id')
                ->where('facebook_messages_disabled', false)
                ->get();

            $successCount = 0;
            $failCount = 0;

            foreach ($customers as $customer) {
                try {
                    $response = Http::post('https://graph.facebook.com/v18.0/me/messages', [
                        'access_token' => $this->pageAccessToken,
                        'recipient' => [
                            'id' => $customer->facebook_id,
                        ],
                        'message' => [
                            'text' => $message,
                        ],
                        'messaging_type' => 'MESSAGE_TAG',
                        // Commonly used tag for post-purchase updates outside 24h; adjust if needed.
                        'tag' => 'post_purchase_update',
                    ]);

                    if ($response->successful()) {
                        $successCount++;
                        Log::info("Expired subscriber message sent", [
                            'customer_id' => $customer->id,
                            'facebook_id' => $customer->facebook_id,
                        ]);
                    } else {
                        $error = $response->json();

                        if (isset($error['error']['code'])) {
                            switch ($error['error']['code']) {
                                case 10:
                                    if (isset($error['error']['error_subcode']) && $error['error']['error_subcode'] === 2018278) {
                                        Log::warning('Message outside allowed time window', [
                                            'customer_id' => $customer->id,
                                            'facebook_id' => $customer->facebook_id,
                                        ]);
                                    }
                                    break;
                                case 551:
                                    if (isset($error['error']['error_subcode']) && $error['error']['error_subcode'] === 1545041) {
                                        // User blocked messages / deactivated
                                        $customer->facebook_messages_disabled = true;
                                        $customer->facebook_disabled_at = now();
                                        $customer->facebook_disabled_reason = 'User blocked messages or deactivated account';
                                        $customer->save();
                                        Log::warning('Customer blocked messages or deactivated', [
                                            'customer_id' => $customer->id,
                                            'facebook_id' => $customer->facebook_id,
                                        ]);
                                    }
                                    break;
                                default:
                                    Log::error('Facebook API error while sending to expired subscriber', [
                                        'customer_id' => $customer->id,
                                        'facebook_id' => $customer->facebook_id,
                                        'error' => $error,
                                    ]);
                            }
                        }

                        $failCount++;
                    }

                    // Rate limiting protection
                    usleep(250000); // 250ms
                } catch (\Exception $e) {
                    $failCount++;
                    Log::error('Exception sending expired subscriber message', [
                        'customer_id' => $customer->id ?? null,
                        'facebook_id' => $customer->facebook_id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'total_customers' => $customers->count(),
                'success_count' => $successCount,
                'fail_count' => $failCount,
            ];
        } catch (\Exception $e) {
            Log::error('Error in SendManualMessageToExpiredSubscribers', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
