<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

class ChatController extends Controller
{
    public function index()
    {
        $conversations = Conversation::with(['latestMessage'])
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return view('chat.index', compact('conversations'));
    }

    public function show(Conversation $conversation)
    {
        $messages = Message::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('chat.show', compact('conversation', 'messages'));
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            // Create and save the message
            $message = new Message();
            $message->conversation_id = $conversation->id;
            $message->content = $request->message;
            $message->type = 'outgoing';
            $message->sender_type = 'admin';
            $message->admin_id = auth()->id(); // Add the admin ID
            $message->save();

            // Update conversation timestamp
            $conversation->last_message_at = now();
            $conversation->save();

            DB::commit();

            // Format response before sending Facebook message
            $response = [
                'id' => $message->id,
                'content' => $message->content,
                'type' => $message->type,
                'sender_type' => $message->sender_type,
                'created_at' => $message->created_at->format('M j, g:i a')
            ];

            // Send message to Facebook asynchronously
            try {
                $this->sendFacebookMessage($conversation->facebook_user_id, $request->message);
            } catch (\Exception $e) {
                \Log::error('Failed to send Facebook message: ' . $e->getMessage());
                // Don't fail the request if Facebook send fails
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to save message: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send message'], 500);
        }
    }

    public function toggleResponseMode(Conversation $conversation)
    {
        $conversation->update([
            'response_mode' => $conversation->response_mode === 'ai' ? 'manual' : 'ai'
        ]);

        return response()->json([
            'status' => 'success',
            'response_mode' => $conversation->response_mode
        ]);
    }

    public function getNewMessages(Request $request, Conversation $conversation)
    {
        $since = $request->input('since');
        $query = Message::where('conversation_id', $conversation->id);
        
        if ($since) {
            $query->where('created_at', '>', $since);
        }
        
        $messages = $query->orderBy('created_at', 'asc')
            ->get()
            ->unique('id')
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'type' => $message->type,
                    'sender_type' => $message->sender_type,
                    'created_at' => $message->created_at->format('M j, g:i a')
                ];
            });

        return response()->json($messages);
    }

    public function getUpdates(Request $request)
    {
        $conversations = Conversation::with('latestMessage')
            ->where('last_message_at', '>', $request->input('since'))
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->unique('id') // Ensure no duplicate conversations
            ->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'facebook_user_id' => $conversation->facebook_user_id,
                    'response_mode' => $conversation->response_mode,
                    'latest_message' => $conversation->latestMessage?->content,
                    'last_message_at' => $conversation->last_message_at,
                    'last_message_at_human' => $conversation->last_message_at?->diffForHumans()
                ];
            });

        return response()->json([
            'conversations' => $conversations,
            'current_time' => now()->toIso8601String()
        ]);
    }

    protected function sendFacebookMessage($recipientId, $messageText)
    {
        try {
            $response = Http::withToken(config('services.facebook.page_access_token'))
                ->post('https://graph.facebook.com/v18.0/me/messages', [
                    'recipient' => ['id' => $recipientId],
                    'message' => ['text' => $messageText]
                ]);

            if (!$response->successful()) {
                \Log::error('Facebook API Error:', [
                    'status' => $response->status(),
                    'body' => $response->json()
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Facebook Message Send Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
