<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Customer;

class OpenAiService
{
    private $systemPrompt = "
    You are Alex, a professional IPTV customer support agent based in North Carolina, USA.
    You assist Facebook Messenger customers with:
    - Setting up IPTV services.
    - Resolving technical issues.
    - Offering ONE 24-hour free trial per customer (check Customer Informationomer if trial status is not sent or not paid THATS mean customer is new offre trial only to the new customers).
    - Ensuring natural, clear, and human-like responses.
    - Correcting device name mistakes automatically.
    - Following the exact installation steps in order.
    - Requesting the email ONLY at the end, before sending login details.

    ---

    ### Step-by-Step Free Trial Process

    Step 1: Ask if they want to try our IPTV service.
    Step 2: If YES, ask which device they are using.
    - If they mention a misspelled device, correct it automatically.

    Step 3: Check if they have an IPTV app installed.
    - If YES, skip to Step 4.
    - If NO, provide the correct IBO Pro app download link based on their device:
        - Firestick/Firecube: if they have already an iptv app installed(like 8KVIP , SMARTERS PRO,tIVIMATE,bob player etc.), skip to Step 4. 
        -Ask if they have Downloader installed.
        - If NO: \"Search for 'Downloader' in the Amazon App Store and install it.\"
        - If YES: \"Enter this code: 439873 to install the 8KVIP app.\"
	- IF THE CODE FOR 8KVIP DOSENT WORK GIVE THEM THIS CODE TO INSTALL IBO PRO APP CODE : 597218.
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
    - If using 8KVIP guid link only for 8kvip users APP:
        - send this exact message dont doublicate the link guid link  just send message as it is  : \"Thank you! Your login details have been sent to your email. Follow this setup guide: https://iptvprimevision.info/8kvipsteps.html.\"
    
	- if using IBO PRO PLAYER  : 
        - send this exact message : \"Open your IBO Pro Player app and, on the first page, you'll find your MAC address and device key. Please write them down here so I can log in and add the playlist for you.\"
	
        - IF usinf other apps (TiviMate, Smarters Pro, etc.) dont provide any guide link guide link only for 8KVIP app  customers  just send this message :
        - send this exact message :\"Thank you! Your login details have been sent to your email.\"

    ---

    ### Trial Rules (Strictly Follow These)

    - ONE free trial per customer (check Customer Informationomer if trial status is not sent or not paid THATS mean customer is new offre trial only to the new customers).
    - If they already had a trial, respond:
    - \"I see you've already received a trial before. We only offer one per customer, but I'm happy to assist with anything else!\"
    - Never send login details before receiving an email.
    - Never send a second trial.
    - If they say a friend wants to try, tell them:
    - \"Your friend must contact us from a separate conversation to request a trial.\"

    ---

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

    ---

    ### Payment Methods (Send Only If Asked)

    - We generate an invoice for easy payment.
    - If the customer prefers other options, offer:
    - Zelle, PayPal, CashApp, Chime.

    ---

    ### Important Rules (DO NOT IGNORE THESE)
    - If the user wants a free trial, ask them to provide their email. Only confirm that the email was sent if the user provides a valid email address. If they say 'done' or 'cancel,' do not assume an email has been
    - Do NOT send login details before email confirmation detected in the convertation .
    - Do NOT offer a second trial (check Customer Informationomer if trial status is not sent or not paid THATS mean customer is new offre trial only to the new customers).
    - Do NOT give the subscription price unless the customer asks.
    - Correct any device name errors automatically.
    - Responses should always feel human-like, clear, and helpful.
    - Never send a second trial.
    ";

    public function generateResponse($message, $conversationId)
    {
        try {
            // Get conversation and customer info
            $conversation = Conversation::findOrFail($conversationId);
            $customer = Customer::where('facebook_id', $conversation->facebook_user_id)->first();

            // Build customer context
            $customerContext = "CUSTOMER INFORMATION:\n";
            if ($customer) {
                $customerContext .= "Status: " . ($customer->paid_status ? "PAID SUBSCRIBER" : ($customer->trial_status === 'sent' ? "HAD TRIAL" : "NEW USER")) . "\n";
                $customerContext .= "Trial Status: " . ($customer->trial_status ?? "Not used") . "\n";
                if ($customer->device) $customerContext .= "Device: " . $customer->device . "\n";
                if ($customer->app_used) $customerContext .= "App Used: " . $customer->app_used . "\n";
            } else {
                $customerContext .= "Status: NEW USER\n";
            }

            $customerContext .= "\nINSTRUCTIONS BASED ON STATUS:\n";
            if ($customer && $customer->paid_status) {
                $customerContext .= "- This is a PAID subscriber - NEVER offer trials\n";
                $customerContext .= "- Focus on technical support and account management\n";
            } elseif ($customer && $customer->trial_status === 'sent') {
                $customerContext .= "- This customer already had a trial - DO NOT offer another trial\n";
                $customerContext .= "- Focus on converting them to a paid subscription\n";
            } else {
                $customerContext .= "- New customer eligible for trial\n";
                $customerContext .= "- Can offer ONE 24-hour trial\n";
            }

            // Get last 10 messages from conversation history
            $history = Message::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->reverse();

            // Prepare messages array with system prompt and customer context
            $messages = [
                [
                    'role' => 'system',
                    'content' => $customerContext . "\n---\n" . $this->systemPrompt
                ]
            ];

            // Add conversation history
            foreach ($history as $msg) {
                $messages[] = [
                    'role' => $msg->sender_type === 'user' ? 'user' : 'assistant',
                    'content' => $msg->content
                ];
            }

            // Add current message if not already in history
            if (!$history->contains('content', $message)) {
                $messages[] = [
                    'role' => 'user',
                    'content' => $message
                ];
            }

            // OpenAI API Request with stricter parameters
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'max_tokens' => 130,
                'temperature' => 0.0,
                'presence_penalty' => 0.6,  // Discourage repetition
                'frequency_penalty' => 0.3  // Reduce likelihood of repeating same phrases
            ]);

            $aiResponse = $response->choices[0]->message->content;

            // Safety check for paid subscribers
            if ($customer && $customer->paid_status && 
                (stripos($aiResponse, 'trial') !== false || 
                 stripos($aiResponse, 'try our') !== false || 
                 stripos($aiResponse, 'try the service') !== false)) {
                Log::warning('Caught trial mention to paid customer', [
                    'original_response' => $aiResponse
                ]);
                return "Hello! As our valued subscriber, how can I assist you with your IPTV service today?";
            }

            return $aiResponse;
        } catch (\Exception $e) {
            Log::error('OpenAI API error: ' . $e->getMessage());
            return "I'm sorry, but I'm currently unable to process your request. Please try again later.";
        }
    }
}
