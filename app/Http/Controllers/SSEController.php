<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SSEController extends Controller
{
    public function stream(Request $request)
    {
        // Set unlimited time limit for this script
        set_time_limit(0);
        ignore_user_abort(true);

        $response = new StreamedResponse(function () use ($request) {
            $lastCheck = now();
            $retryCount = 0;
            $maxRetries = 30; // 30 seconds max connection time

            while ($retryCount < $maxRetries) {
                if (connection_aborted()) {
                    break;
                }

                try {
                    // Get updates for conversations
                    if ($request->has('conversation_id')) {
                        $messages = Message::where('conversation_id', $request->conversation_id)
                            ->where('created_at', '>', $lastCheck)
                            ->get();

                        if ($messages->isNotEmpty()) {
                            echo "data: " . json_encode([
                                'type' => 'messages',
                                'data' => $messages->map(function ($message) {
                                    return [
                                        'id' => $message->id,
                                        'content' => $message->content,
                                        'type' => $message->type,
                                        'sender_type' => $message->sender_type,
                                        'created_at' => $message->created_at->diffForHumans(),
                                    ];
                                })
                            ]) . "\n\n";
                            
                            ob_flush();
                            flush();
                        }
                    } else {
                        $conversations = Conversation::with('latestMessage')
                            ->where('last_message_at', '>', $lastCheck)
                            ->get();

                        if ($conversations->isNotEmpty()) {
                            echo "data: " . json_encode([
                                'type' => 'conversations',
                                'data' => $conversations->map(function ($conversation) {
                                    return [
                                        'id' => $conversation->id,
                                        'facebook_user_id' => $conversation->facebook_user_id,
                                        'response_mode' => $conversation->response_mode,
                                        'latest_message' => $conversation->latestMessage?->content,
                                        'last_message_at' => $conversation->last_message_at,
                                        'last_message_at_human' => $conversation->last_message_at?->diffForHumans()
                                    ];
                                })
                            ]) . "\n\n";
                            
                            ob_flush();
                            flush();
                        }
                    }

                    $lastCheck = now();
                    $retryCount++;
                    
                    // Send a keep-alive comment every second
                    echo ": keepalive\n\n";
                    ob_flush();
                    flush();
                    
                    sleep(1);
                } catch (\Exception $e) {
                    // Log error and break the loop
                    \Log::error('SSE Error: ' . $e->getMessage());
                    break;
                }
            }

            // Send a retry message to the client
            echo "event: retry\ndata: null\n\n";
            ob_flush();
            flush();
        });

        // Set headers
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');
        
        // Prevent FastCGI from buffering
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }

        return $response;
    }
}
