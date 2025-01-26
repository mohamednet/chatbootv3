<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Conversation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MessageProcessor
{
    private $openAiService;
    private $facebookToken;

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
        $this->facebookToken = config('services.facebook.page_access_token');
    }

    public function processMessages(Conversation $conversation)
    {
        try {
            // Get unprocessed messages older than 10 seconds
            $messages = Message::where('conversation_id', $conversation->id)
                ->where('processed', false)
                ->where('type', 'incoming')
                ->where('created_at', '<=', Carbon::now()->subSeconds(10))
                ->whereNull('processing_started_at')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($messages->isEmpty()) {
                return null;
            }

            // Mark messages as being processed
            Message::whereIn('id', $messages->pluck('id'))
                ->update(['processing_started_at' => Carbon::now()]);

            // Combine all messages into one context
            $combinedMessage = $messages->pluck('content')->implode("\n");

            // Get AI response
            $aiResponse = $this->openAiService->generateResponse($combinedMessage);

            // Send response to Facebook
            $this->sendFacebookMessage($conversation->facebook_user_id, $aiResponse);

            // Create response message in database
            $responseMessage = new Message([
                'conversation_id' => $conversation->id,
                'content' => $aiResponse,
                'type' => 'outgoing',
                'sender_type' => 'ai',
                'processed' => true
            ]);
            $responseMessage->save();

            // Mark original messages as processed
            Message::whereIn('id', $messages->pluck('id'))
                ->update(['processed' => true]);

            // Update conversation timestamp
            $conversation->touch();

            return $responseMessage;

        } catch (\Exception $e) {
            Log::error('Error processing messages: ' . $e->getMessage());
            return null;
        }
    }

    private function sendFacebookMessage($recipientId, $message)
    {
        try {
            $response = Http::post('https://graph.facebook.com/v18.0/me/messages', [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $message],
                'access_token' => $this->facebookToken
            ]);

            if (!$response->successful()) {
                Log::error('Facebook API error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Error sending Facebook message: ' . $e->getMessage());
        }
    }
}
