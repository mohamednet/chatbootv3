<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Customer;

class CustomerDataAnalysisService
{
    private $systemPrompt = "
    IMPORTANT:
    Your task is to assist customers and dynamically track the following information during the conversation:
    - Device: Identify the customer's device based on their messages (e.g., Firestick, Android, iPhone, etc.).
    - App: Identify the IPTV app the customer is using or planning to use (e.g., Tivimate, IBO Pro Player, etc.).
    - Email: Detect the customer's email address when provided.

    CRITICAL - YOU MUST FORMAT YOUR ENTIRE RESPONSE AS JSON:
    You must ALWAYS return your COMPLETE response in this exact JSON format, with no additional text before or after so I can work on it in the backend:

    {
        response: \"Your actual message to the customer goes here\",
        customers_data: {
            device: null,
            app: null,
            email: null
        }
    }

    IMPORTANT:
    - Your ENTIRE response must be valid JSON with response and customers_data fields always present.
    - Do not add any text before or after the JSON.
    - Always include both response and customers_data.
    - Update customer data when you detect new information.
    - Keep previously detected values unless the customer changes them.";

    public function analyzeCustomerData($conversationId)
    {
        try {
            // Get recent conversation history (last 10 messages)
            $history = Message::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->reverse();

            // Build messages array with conversation history
            $messages = [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt
                ]
            ];

            // Add conversation history with proper context
            $currentContext = "";
            foreach ($history as $msg) {
                if ($msg->sender_type === 'user') {
                    $messages[] = [
                        'role' => 'user',
                        'content' => $msg->content
                    ];
                    $currentContext .= "User: " . $msg->content . "\n";
                } else {
                    $messages[] = [
                        'role' => 'assistant',
                        'content' => $msg->content
                    ];
                    $currentContext .= "Assistant: " . $msg->content . "\n";
                }
            }

            // Add current context to system message
            $messages[0]['content'] .= "\n\nCurrent Conversation:\n" . $currentContext;

            Log::info('Analyzing customer data', [
                'conversation_id' => $conversationId,
                'context' => $currentContext
            ]);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'temperature' => 0.3, // Lower temperature for more consistent data extraction
            ]);

            $aiResponse = json_decode($response->choices[0]->message->content, true);
            
            //here update the customer data
            if (isset($aiResponse['customers_data'])) {
                try {
                    Customer::where('conversation_id', $conversationId)
                        ->update([
                            'device' => $aiResponse['customers_data']['device'],
                            'app' => $aiResponse['customers_data']['app'],
                            'email' => $aiResponse['customers_data']['email']
                        ]);
                    
                    // Check if email was just detected
                    if (!empty($aiResponse['customers_data']['email'])) {
                        // Update conversation mode to manual
                        Conversation::where('id', $conversationId)
                            ->update(['response_mode' => 'manual']);
                        
                        Log::info('Email detected and mode changed to manual', [
                            'conversation_id' => $conversationId,
                            'email' => $aiResponse['customers_data']['email']
                        ]);
                    }

                    Log::info('Customer data updated', [
                        'conversation_id' => $conversationId,
                        'data' => $aiResponse['customers_data']
                    ]);

                    return true;
                } catch (\Exception $e) {
                    Log::error('Failed to update customer data: ' . $e->getMessage());
                    return false;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error analyzing customer data: ' . $e->getMessage());
            return false;
        }
    }
}
