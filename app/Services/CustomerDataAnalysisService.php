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
    private $systemPrompt =  "
    You are a strict data extraction assistant.
    Your ONLY job is to extract the following information from the user's messages:
    - device: The device being used (e.g., Firestick, Android TV, iPhone, etc.)
    - app: The IPTV app mentioned (e.g., Tivimate, IBO Pro Player, IPTV Smarters)
    - email: Any provided email address

    You MUST return ONLY this exact JSON — nothing else:

    {
    'customers_data': {
        'device': null,
        'app': null,
        'email': null
    }
    }

    RULES:
    - Use double quotes for all keys and string values (standard JSON)
    - Return actual JSON — NOT a string containing JSON
    - Do NOT wrap output in markdown (```json)
    - Do NOT include any extra explanation or commentary
    - If a value is not yet detected, use null                      
   ";

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
                } else {
                    $messages[] = [
                        'role' => 'assistant',
                        'content' => $msg->content
                    ];
                }
            }

            Log::info('Conversation history', [
                'message history 11111' => $messages,
            ]);

            $messages[] = [
                'role' => 'user',
                'content' => 'Please analyze all the above messages and return only the customers_data JSON.'
            ];

            // Add current context to system message
    

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.2, // Lower temperature for more consistent data extraction
            ]);

            $aiResponse = json_decode($response->choices[0]->message->content, true);
            
          
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
