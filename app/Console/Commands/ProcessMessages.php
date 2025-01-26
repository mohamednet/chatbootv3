<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessMessages extends Command
{
    protected $signature = 'messages:process';
    protected $description = 'Process unprocessed messages in AI mode conversations';

    private $messageProcessor;

    public function __construct(MessageProcessor $messageProcessor)
    {
        parent::__construct();
        $this->messageProcessor = $messageProcessor;
    }

    public function handle()
    {
        try {
            Log::info('Starting message processing cycle');

            $conversations = Conversation::where('response_mode', 'ai')
                ->whereHas('messages', function ($query) {
                    $query->where('processed', false)
                        ->whereNull('processing_started_at')
                        ->where('type', 'incoming')
                        ->where('created_at', '<=', now()->subSeconds(10));
                })
                ->get();

            Log::info('Found conversations to process', [
                'count' => $conversations->count(),
                'conversation_ids' => $conversations->pluck('id')
            ]);

            foreach ($conversations as $conversation) {
                try {
                    Log::info('Processing conversation', [
                        'conversation_id' => $conversation->id,
                        'facebook_user_id' => $conversation->facebook_user_id
                    ]);

                    $unprocessedMessages = Message::where('conversation_id', $conversation->id)
                        ->where('processed', false)
                        ->where('type', 'incoming')
                        ->whereNull('processing_started_at')
                        ->where('created_at', '<=', now()->subSeconds(10))
                        ->get();

                    Log::info('Found unprocessed messages', [
                        'conversation_id' => $conversation->id,
                        'message_count' => $unprocessedMessages->count(),
                        'message_ids' => $unprocessedMessages->pluck('id')
                    ]);

                    if ($unprocessedMessages->isNotEmpty()) {
                        $response = $this->messageProcessor->processMessages($conversation);
                        
                        if ($response) {
                            Log::info('Successfully processed messages', [
                                'conversation_id' => $conversation->id,
                                'response_id' => $response->id
                            ]);
                        } else {
                            Log::warning('No response generated for messages', [
                                'conversation_id' => $conversation->id
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing conversation: ' . $e->getMessage(), [
                        'conversation_id' => $conversation->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            Log::info('Completed message processing cycle');

        } catch (\Exception $e) {
            Log::error('Error in message processing command: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
