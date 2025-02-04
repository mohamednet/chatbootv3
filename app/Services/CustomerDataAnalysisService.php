<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrialCredentials;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Trial;
use Exception;

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
            // Get all conversation history
            $history = Message::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'asc')  // Get messages in chronological order
                ->get();

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
                    if (!empty($aiResponse['customers_data']['email']) 
                    && (!empty($aiResponse['customers_data']['device']) 
                        || !empty($aiResponse['customers_data']['app']))) {
                        // Get customer record to check trial status
                        $customer = Customer::where('conversation_id', $conversationId)->first();
                        
                        // Only change to manual mode if trial hasn't been sent yet
                        if ($customer && $customer->trial_status !== 'Sent') {
                            // Update conversation mode to manual
                            Conversation::where('id', $conversationId)
                                ->update(['response_mode' => 'manual']);
                            

                            //Select an available trial that's less than 18 hours old
                            $availableTrial = Trial::whereNull('assigned_user')
                                ->where('created_at', '>=', now()->subHours(18))
                                ->orderBy('created_at', 'asc')
                                ->first();

                            if ($availableTrial) {
                                $availableTrial->update([
                                    'assigned_user' => $customer->facebook_id
                                ]);

                                // Send trial credentials email
                                try {
                                    Mail::to($aiResponse['customers_data']['email'])
                                        ->send(new TrialCredentials($availableTrial));
                                    
                                    // Update customer trial status to sent
                                    $customer->update([
                                        'trial_status' => 'Sent'
                                    ]);

                                    Log::info('Trial credentials sent successfully', [
                                        'trial_id' => $availableTrial->id,
                                        'customer_email' => $aiResponse['customers_data']['email']
                                    ]);
                                } catch (Exception $e) {
                                    // Update customer trial status to error
                                    $customer->update([
                                        'trial_status' => 'Error Sending Email'
                                    ]);

                                    Log::error('Failed to send trial credentials', [
                                        'trial_id' => $availableTrial->id,
                                        'customer_email' => $aiResponse['customers_data']['email'],
                                        'error' => $e->getMessage()
                                    ]);
                                }

                                Log::info('Trial assigned to customer', [
                                    'trial_id' => $availableTrial->id,
                                    'customer_id' => $customer->facebook_id,
                                    'trial_created_at' => $availableTrial->created_at
                                ]);
                            }
                            
                            Log::info('Email detected and mode changed to manual', [
                                'conversation_id' => $conversationId,
                                'email' => $aiResponse['customers_data']['email'],
                                'trial_status' => $customer->trial_status
                            ]);
                        } else {
                            Log::info('Email detected but trial already sent, keeping current mode', [
                                'conversation_id' => $conversationId,
                                'email' => $aiResponse['customers_data']['email'],
                                'trial_status' => $customer ? $customer->trial_status : 'Unknown'
                            ]);
                        }
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
