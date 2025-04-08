<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Customer;
use App\Jobs\ProcessAIResponse;
use App\Services\CustomerDataAnalysisService;
use App\Services\IboProAddPlaylistService; // Added import statement
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Jobs\ProcessIboProImage;

class FacebookWebhookController extends Controller
{
    private $customerDataService;

    public function __construct(CustomerDataAnalysisService $customerDataService)
    {
        $this->customerDataService = $customerDataService;
    }

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
                // Check if this is an echo message (sent from page)
                if (isset($messagingEvent['message']['is_echo']) && isset($messagingEvent['message']['app_id']) && $messagingEvent['message']['app_id'] != config('services.facebook.app_id')) {
                    // chech if message  is  *  

                    //change just here
                    if (isset($messagingEvent['message']['text']) && trim(strtolower($messagingEvent['message']['text'])) === '*') {
                        Log::info('Switching to AI mode', [
                            'recipient_id' => $messagingEvent['recipient']['id']
                        ]);
                        
                        $conversation = Conversation::where('facebook_user_id', $messagingEvent['recipient']['id'])->first();
                        if ($conversation) {
                            $conversation->response_mode = 'ai';
                            $conversation->save();
                            Log::info('Conversation switched to AI mode', [
                                'conversation_id' => $conversation->id
                            ]);
                        }
                        return;
                    }
                    //here
                    return $this->handlePageMessage($messagingEvent);
                }else if(isset($messagingEvent['message']['is_echo']) && isset($messagingEvent['message']['app_id']) && $messagingEvent['message']['app_id'] == config('services.facebook.app_id')) {
                    return ;
                }
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

    private function handlePageMessage($messagingEvent)
    {
        try {
            $recipientId = $messagingEvent['recipient']['id'];
            $messageId = $messagingEvent['message']['mid'];
            
            Log::info('Processing page message', [
                'recipient_id' => $recipientId,
                'message_id' => $messageId
            ]);

            // Get or create conversation and customer
            $conversation = Conversation::firstOrCreate(
                ['facebook_user_id' => $recipientId],
                [
                    'response_mode' => 'manual', // Set to manual mode for page messages
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($conversation->wasRecentlyCreated) {
                Log::info('Created new conversation for page message', ['conversation_id' => $conversation->id]);
            }

            $customer = Customer::firstOrCreate(
                ['facebook_id' => $recipientId],
                [
                    'conversation_id' => $conversation->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($customer->wasRecentlyCreated) {
                Log::info('Created new customer for page message', ['customer_id' => $customer->id]);
            }

            // Extract message content
            $messageContent = '';
            if (isset($messagingEvent['message']['text'])) {
                $messageContent = $messagingEvent['message']['text'];
            } elseif (isset($messagingEvent['message']['attachments'])) {
                $attachment = $messagingEvent['message']['attachments'][0];
                $messageContent = '[Attachment: [type: ' . $attachment['type'] . ', url: \'' . $attachment['payload']['url'] . '\']]';
            }

            // Store the outgoing message
                // Update conversation to manual mode and clear pending responses
                if (isset($messagingEvent['message']['text']) && trim(strtolower($messagingEvent['message']['text'])) === '-'){
                    $conversation->update([
                        'response_mode' => 'manual',
                        'updated_at' => now()
                    ]);
                }else {
                    $message = new Message([
                        'conversation_id' => $conversation->id,
                        'content' => $messageContent,
                        'type' => 'outgoing',
                        'sender_type' => 'admin',
                        'facebook_message_id' => $messageId,
                        'processed' => true // Mark as processed immediately
                    ]);
                    $message->save();
                }

            Log::info('Processing message:', ['content' => $messageContent]);

            if (str_starts_with(trim($messageContent), 'PAID')) {
                Log::info('Found PAID message');
                
                try {
                    // Extract subscription information using regex
                    preg_match('/ID: ([\w-]+)/', $messageContent, $idMatches);
                    preg_match('/Device: (\d+)/', $messageContent, $deviceMatches);
                    preg_match('/Subscription: (\d+)\s*months?/', $messageContent, $subMatches);
                    preg_match('/Plan: (\w+)/', $messageContent, $planMatches);
                    preg_match('/Amount: \$(\d+)/', $messageContent, $amountMatches);
                    preg_match('/Payment: (\w+)/', $messageContent, $paymentMatches);

                    Log::info('Regex matches:', [
                        'id' => $idMatches ?? 'no match',
                        'device' => $deviceMatches ?? 'no match',
                        'subscription' => $subMatches ?? 'no match',
                        'plan' => $planMatches ?? 'no match',
                        'amount' => $amountMatches ?? 'no match',
                        'payment' => $paymentMatches ?? 'no match'
                    ]);

                    if ($idMatches && $deviceMatches && $subMatches) {
                        $subscriptionId = $idMatches[1];
                        $devices = (int)$deviceMatches[1];
                        $months = (int)$subMatches[1];
                        $plan = $planMatches[1] ?? 'Basic';
                        $amount = $amountMatches[1] ?? 0;
                        $paymentMethod = $paymentMatches[1] ?? 'Unknown';

                        Log::info('Extracted values:', [
                            'subscriptionId' => $subscriptionId,
                            'devices' => $devices,
                            'months' => $months,
                            'plan' => $plan,
                            'amount' => $amount,
                            'paymentMethod' => $paymentMethod
                        ]);

                        // Calculate subscription end date
                        $endDate = now()->addMonths($months);

                        // Update customer subscription details
                        $customer->update([
                            'paid_status' => true,
                            'subscription_id' => $subscriptionId,
                            'subscription_end_date' => $endDate,
                            'last_payment_date' => now(),
                            'subscription_type' => $months . ' months',
                            'number_of_devices' => $devices,
                            'plan' => $plan,
                            'amount' => $amount,
                            'payment_method' => $paymentMethod
                        ]);

                        Log::info('Updated customer subscription', [
                            'customer_id' => $customer->id,
                            'subscription_id' => $subscriptionId,
                            'devices' => $devices,
                            'months' => $months,
                            'end_date' => $endDate
                        ]);
                    } else {
                        Log::warning('Required matches not found in PAID message');
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing subscription message', [
                        'message' => $messageContent,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Stored page message', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'content' => $messageContent
            ]);

        

            // Mark any unprocessed messages as processed
            Message::where('conversation_id', $conversation->id)
                  ->where('processed', false)
                  ->update(['processed' => true]);

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Error handling page message: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'messaging_event' => $messagingEvent
            ]);
            throw $e;
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

            // Create customer record if it doesn't exist
            $customer = Customer::firstOrCreate(
                ['facebook_id' => $senderId],
                ['conversation_id' => $conversation->id]
            );

            // Extract message content safely
            $messageContent = '';
            $hasAttachment = false;
            $attachment = '';
            if (isset($messagingEvent['message'])) {
                if (isset($messagingEvent['message']['text'])) {
                    $messageContent = $messagingEvent['message']['text'];
                } elseif (isset($messagingEvent['message']['quick_reply'])) {
                    $messageContent = $messagingEvent['message']['quick_reply']['payload'];
                } elseif (isset($messagingEvent['message']['attachments'])) {
                    $hasAttachment = true;
                    $attachment = $messagingEvent['message']['attachments'][0];
                    $messageContent = '[Attachment: [type: ' . $attachment['type'] . ', url: \'' . $attachment['payload']['url'] . '\']]';
                }
            }

            Log::info('Extracted message content', [
                'content' => $messageContent,
                'conversation_id' => $conversation->id,
                'has_attachment' => $hasAttachment
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

            // If message has attachment, switch to manual mode
            if ($hasAttachment) {
                $conversation->update(['response_mode' => 'manual']);
                $message->update(['processed' => true]);
                Log::info('Switched to manual mode due to attachment', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id
                ]);
                 //call the job here
                //check if cutomers has device and app and email and trial is sent 
                $customer = Customer::find($senderId);
                //check if colmn not null
                      
              if($customer->device && $customer->app && $customer->email && stripos($customer->app, "IBO") !== false 
              && strcasecmp($customer->trial_status, 'Sent') === 0 && $customer->paid_status == false
              && is_null($customer->ibopro_mac_address)) {
                ProcessIboProImage::dispatch($attachment['payload']['url'], $senderId);
              }
                
                return;
            }

            // Analyze customer data
            $this->customerDataService->analyzeCustomerData($conversation->id);

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
