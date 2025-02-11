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

    public function sendMessage(string $facebookId, string $message): void
    {
        try {
            $response = Http::post("https://graph.facebook.com/v18.0/me/messages", [
                'recipient' => ['id' => $facebookId],
                'message' => ['text' => $message],
                'access_token' => $this->facebookToken
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send Facebook message', [
                    'facebook_id' => $facebookId,
                    'error' => $response->json(),
                    'status' => $response->status()
                ]);
                return;
            }

            Log::info('Facebook message sent successfully', [
                'facebook_id' => $facebookId
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending Facebook message', [
                'facebook_id' => $facebookId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
