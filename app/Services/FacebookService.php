<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService
{
    private $facebookToken;

    public function __construct()
    {
        $this->facebookToken = config('services.facebook.page_access_token');
    }

    public function sendMessage(string $facebookId, string $message): bool
    {
        try {
            $response = Http::post("https://graph.facebook.com/v18.0/me/messages", [
                'recipient' => ['id' => $facebookId],
                'message' => ['text' => $message],
                'messaging_type' => 'MESSAGE_TAG',
                'tag' => 'post_purchase_update',
                'access_token' => $this->facebookToken
            ]);

            if (!$response->successful()) {
                $error = $response->json();
                // Check if it's a time window restriction
                if (isset($error['error']['code']) && $error['error']['code'] === 10 && 
                    isset($error['error']['error_subcode']) && $error['error']['error_subcode'] === 2018278) {
                    Log::warning('Message outside Facebook allowed time window', [
                        'facebook_id' => $facebookId
                    ]);
                    return false;
                }
                
                Log::error('Failed to send Facebook message', [
                    'facebook_id' => $facebookId,
                    'error' => $error,
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to send Facebook message: ' . json_encode($error));
            }

            Log::info('Facebook message sent successfully', [
                'facebook_id' => $facebookId
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending Facebook message', [
                'facebook_id' => $facebookId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
