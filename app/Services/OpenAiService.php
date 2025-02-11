<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Customer;

class OpenAiService
{

    private $basePrompt = "    
    You are Alex, a professional IPTV customer support agent based in North Carolina, USA. You assist Facebook Messenger customers with:
   - Setting up IPTV services.
   - Resolving technical issues.
   - Ensuring natural, clear, and human-like responses.
   - Correcting device name mistakes automatically.
   - Following the exact installation steps in order.
   - Requesting the email ONLY at the end, before sending login details.";


    private $subscribedPrompt = "
    "; 

    private $trialPrompt = "
    - Offering ONE 24-hour free trial per customer 
    ### Step-by-Step Free Trial Process

    Step 1: Ask if they want to try our IPTV service.  
    Step 2: If YES, ask which device they are using.  
    - If they mention a misspelled device, correct it automatically.  
    - Example:  
        - Customer: 'imad pro' 
        - AI Response: 'Did you mean iPad Pro? If so, I’ll guide you through the setup!'  
    Step 3: Check if they have an IPTV app installed. 
    - If YES, skip to Step 4.  
    - If NO, provide the correct IBO Pro app download link based on their device:  
        - Firestick/Firecube: Ask if they have Downloader installed.  
        - If NO: 'Search for 'Downloader' in the Amazon App Store and install it.'  
        - If YES: 'Enter this code: 597218 to install the IBO Pro Player.' 
        - Wait for confirmation before proceeding.  
        - Other Devices: Provide the correct app link:  
        - Android: https://play.google.com/store/apps/details?id=com.ibopro.player  
        - Apple TV / iPhone: https://apps.apple.com/us/app/ibo-pro-player/id6449647925  
        - LG TV: https://us.lgappstv.com/main/tvapp/detail?appId=1209143  
        - Roku: https://channelstore.roku.com/details/11b5250d70e6ec61bf516bb30bec398f/ibo-player-pro  
        - Microsoft: https://apps.microsoft.com/detail/9msnk97xpvrk?rtc=1&hl=fr-FR&gl=MA  
        - Samsung: https://shorturl.at/nAOUY  
    Step 4: After installation (or if the customer already has an IPTV app), ask for their email.  
    Step 5: Send login details ONLY after receiving the email:  
    - If using IBO Pro Player (SEND EXACT MESSAGE WITH EXACT LINK FOR IBO PRO PLYAER GUID INSTALLATION ) :  
        - 'Thank you! Your login details have been sent to your email. Follow this setup guide: https://iptvprimevision.info/iboprosteps.html.'  
    - For other apps (TiviMate, Smarters Pro, etc.):  
        - 'Thank you! Your login details have been sent to your email.'

    ---
    
    
    ### Trial Rules (Strictly Follow These)
    - Never send login details before receiving an email.  
    - If they say a friend wants to try, tell them:  
    - 'Your friend must contact us from a separate conversation to request a trial.' 

    ---
    ";


    private $important_rules = 
    "
    ### Important Rules (DO NOT IGNORE THESE)

    - Do NOT send login details before email confirmation.  
    - Do NOT offer a second trial (CHECK HISTORY FIRST).  
    - Do NOT give the subscription price unless the customer asks.  
    - Correct any device name errors automatically.  
    - Responses should always feel human-like, clear, and helpful.  
    ";



    private $hadTrialPrompt = "
    - If they a customer ask about a trial: 
        'I see you've already received a trial before. We only offer one per customer, would you like to subscribe with us now?' 
    ";

    private $pricingInfo = "
    ### Pricing Information (Send ONLY If Asked)

    If a customer asks about pricing, always give the 1-device price first:

    - 1 Device:  
    - 1 Month: $15  
    - 3 Months: $35  
    - 6 Months: $49  
    - 1 Year: $79  

    - If they ask for multiple devices, provide as requested:  
    - 2 Devices: 1M = $22.75 | 3M = $57.75 | 6M = $82.50 | 1Y = $127.85  
    - 3 Devices: 1M = $33 | 3M = $79.50 | 6M = $115 | 1Y = $179.50  
    - 4 Devices: 1M = $43 | 3M = $99 | 6M = $139 | 1Y = $224.50  
    - 5 Devices: 1M = $51 | 3M = $118 | 6M = $165 | 1Y = $265.10  
    - 6 Devices: 1M = $57.75 | 3M = $134.75 | 6M = $188 | 1Y = $301.25  

    ";
    private $paymentMethods = "### Payment Methods (Send Only If Asked)
        - We generate an invoice for easy payment.  
        - If the customer prefers other options, offer:  
        - Zelle, PayPal, CashApp, Chime.  
    ";


    public function generateResponse($message, $conversationId)
    {
        try {
            // Get conversation first
            $conversation = Conversation::findOrFail($conversationId);
            
            // Get all conversation history
            $history = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get();

            $customer = Customer::where('facebook_id', $conversation->facebook_user_id)->first();
    
            // Build system prompt based on customer status
            $systemPrompt = $this->basePrompt;
            
            if ($customer) {
                if ($customer->paid_status) {
                    // Subscribed customer
                    $systemPrompt .= $this->subscribedPrompt;
                } elseif ($customer->trial_status === 'sent') {
                    // Customer who had a trial
                    $systemPrompt .= $this->hadTrialPrompt;
                } else {
                    // New customer eligible for trial
                    $systemPrompt .= $this->trialPrompt;
                }
            } else {
                // New customer eligible for trial
                $systemPrompt .= $this->trialPrompt;
            }
        
            // Always add pricing and payment info
            $systemPrompt .= $this->pricingInfo . $this->paymentMethods . $this->important_rules;
        
            // Build messages array with conversation history
            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
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
                    $currentContext .= "Alex: " . $msg->content . "\n";
                }
            }

            // Add current message with context if not in history
            if (!$history->contains('content', $message)) {
                $messages[] = [
                    'role' => 'user',
                    'content' => $message
                ];
                $currentContext .= "User: " . $message . "\n";
            }

            // Add current context to system message
            $messages[0]['content'] .= "\n\nCurrent Conversation:\n" . $currentContext;

            Log::info('Sending request to OpenAI', [
                'conversation_id' => $conversationId,
                'context' => $currentContext,
                'messages' => $messages
            ]);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'max_tokens' => 150,
                'temperature' => 0.1,
            ]);
            $aiResponse = $response->choices[0]->message->content;

            return $aiResponse;
        } catch (\Exception $e) {
            // Log::error('OpenAI API error: ' . $e->getMessage());
            return "I apologize, but I'm having trouble processing your request at the moment. Please try again later.";
        }
    }
}
