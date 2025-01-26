<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProcessAIResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $conversation;
    public $tries = 3;
    public $timeout = 30;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }

    public function handle(OpenAiService $openAiService)
    {
        try {
            Log::info('Starting AI response processing', [
                'conversation_id' => $this->conversation->id
            ]);

            // Get unprocessed messages
            $messages = Message::where('conversation_id', $this->conversation->id)
                ->where('processed', false)
                ->where('type', 'incoming')
                ->whereNull('processing_started_at')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($messages->isEmpty()) {
                Log::info('No messages to process');
                return;
            }

            // Mark messages as being processed
            Message::whereIn('id', $messages->pluck('id'))
                ->update(['processing_started_at' => now()]);

            // Combine messages into one context
            $combinedMessage = $messages->pluck('content')->implode("\n");

            // Get AI response with conversation history
            $aiResponse = $openAiService->generateResponse($combinedMessage, $this->conversation->id);

            // Send to Facebook
            try {
                $response = Http::post('https://graph.facebook.com/v18.0/me/messages', [
                    'recipient' => ['id' => $this->conversation->facebook_user_id],
                    'message' => ['text' => $aiResponse],
                    'access_token' => config('services.facebook.page_access_token')
                ]);

                if (!$response->successful()) {
                    Log::error('Facebook API Error:', [
                        'status' => $response->status(),
                        'body' => $response->json()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Facebook Message Send Error:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            // Save response in database
            $responseMessage = new Message([
                'conversation_id' => $this->conversation->id,
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
            $this->conversation->touch();

            Log::info('AI response processed successfully', [
                'conversation_id' => $this->conversation->id,
                'message_count' => $messages->count(),
                'response_id' => $responseMessage->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing AI response: ' . $e->getMessage(), [
                'conversation_id' => $this->conversation->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
