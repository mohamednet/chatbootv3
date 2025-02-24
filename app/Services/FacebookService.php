<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
            // Log attempt to send message
            Log::channel('trial-reminders')->info('Attempting to send message', [
                'facebook_id' => $facebookId,
                'message_length' => strlen($message)
            ]);

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

                // Check if user is unavailable (blocked messages or deactivated account)
                if (isset($error['error']['code']) && $error['error']['code'] === 551 &&
                    isset($error['error']['error_subcode']) && $error['error']['error_subcode'] === 1545041) {
                    Log::warning('User is unavailable on Facebook (blocked or deactivated)', [
                        'facebook_id' => $facebookId
                    ]);
                    
                    // Mark user as unreachable in database
                    try {
                        DB::table('customers')
                            ->where('facebook_id', $facebookId)
                            ->update([
                                'facebook_messages_disabled' => true,
                                'facebook_disabled_at' => now(),
                                'facebook_disabled_reason' => 'User unavailable (blocked or deactivated)'
                            ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to update customer Facebook status', [
                            'facebook_id' => $facebookId,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
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
