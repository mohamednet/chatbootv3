<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Jobs\ProcessAIResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FacebookWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $verifyToken = config('services.facebook.webhook_verify_token');
        
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');
        
        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Webhook verified successfully');
            return response($challenge, 200);
        }
        
        Log::warning('Webhook verification failed', [
            'mode' => $mode,
            'token' => $token
        ]);
        return response('Failed verification', 403);
    }

    public function handleWebhook(Request $request)
    {
        try {
            $data = $request->all();
            Log::info('Received webhook:', ['data' => json_encode($data)]);

            if (isset($data['entry'][0]['messaging'][0])) {
                $messagingEvent = $data['entry'][0]['messaging'][0];
                $senderId = $messagingEvent['sender']['id'];

                $this->handleMessage($messagingEvent, $senderId);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Error in handleWebhook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response('Error', 500);
        }
    }

    private function handleMessage($messagingEvent, $senderId)
    {
        try {
            // Log the full message structure
            Log::info('Full message structure:', [
                'messaging_event' => json_encode($messagingEvent, JSON_PRETTY_PRINT),
                'message' => json_encode($messagingEvent['message'] ?? [], JSON_PRETTY_PRINT)
            ]);

            // Get or create conversation
            $conversation = Conversation::firstOrCreate(
                ['facebook_user_id' => $senderId],
                ['response_mode' => 'ai'] // Default to AI mode
            );

            // Extract message content safely
            $messageContent = '';
            if (isset($messagingEvent['message'])) {
                if (isset($messagingEvent['message']['text'])) {
                    $messageContent = $messagingEvent['message']['text'];
                } elseif (isset($messagingEvent['message']['quick_reply'])) {
                    $messageContent = $messagingEvent['message']['quick_reply']['payload'];
                } elseif (isset($messagingEvent['message']['attachments'])) {
                    $messageContent = '[Attachment: ' . $messagingEvent['message']['attachments'][0]['type'] . ']';
                }
            }

            Log::info('Extracted message content', [
                'content' => $messageContent,
                'conversation_id' => $conversation->id
            ]);

            // Store the incoming message
            $message = new Message([
                'conversation_id' => $conversation->id,
                'content' => $messageContent,
                'type' => 'incoming',
                'sender_type' => 'user',
                'processed' => false
            ]);
            $message->save();

            Log::info('Message saved', [
                'message_id' => $message->id,
                'content' => $message->content
            ]);

            // Update conversation timestamp
            $conversation->touch();

            // If in AI mode, dispatch job with delay
            if ($conversation->response_mode === 'ai') {
                ProcessAIResponse::dispatch($conversation)
                    ->delay(now()->addSeconds(10));
                Log::info('AI processing job dispatched', [
                    'conversation_id' => $conversation->id
                ]);
                return;
            }

            // If in manual mode, mark as processed immediately
            $message->update(['processed' => true]);
            Log::info('Message marked as processed (manual mode)');
        } catch (\Exception $e) {
            Log::error('Error handling message: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'sender_id' => $senderId,
                'message_event' => $messagingEvent
            ]);
            throw $e;
        }
    }
}
