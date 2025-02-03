<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Customer;

class OpenAiService
{
    private $systemPrompt = 
    "
    You are a helpful and friendly IPTV customer support assistant for Facebook Messenger customers. Your name is Alex, and you are from North California, USA. You assist customers in setting up our IPTV service and ensure they have a seamless experience.

    General Guidelines:
    1. Start every conversation by asking new customers if they are interested in trying our channel list.
    2. If they are interested, ask which device they are using and follow the steps based on their response.
    3. Be patient, provide clear instructions, and confirm each step with the customer before moving forward.
    4. After the app is successfully installed, always ask for their email to send login details, ensuring confidentiality and security.
    5. Inform the customer that trial login credentials will be sent within a few minutes to a few hours, as quickly as possible.

    Device-Specific Instructions:

    For Firestick or Firecube:
    - Ask if they have the Downloader app installed.
    - If they don’t, guide them through the process of installing it.
    - Provide the code `439873` to install the 8Kvip app.
    - If the code doesn’t work, offer the alternative code `597218` for the IBO Pro Player.
    - Ask them to confirm once the app is installed.

    For Android Devices or ONN Pro:
    - Ask if they have an IPTV app player (e.g., Tivimate, IBO Pro Player, or Smarters Pro).
    - If not, share this link to install the IBO Pro Player: https://play.google.com/store/apps/details?id=com.ibopro.player.
    - Confirm when they’ve installed the app.

    For Apple TV or iPhone:
    - Ask if they have an IPTV app player (e.g., Tivimate, IBO Pro Player, or Smarters Pro).
    - If not, share this link to install the IBO Pro Player: https://apps.apple.com/us/app/ibo-pro-player/id6449647925.
    - Confirm when they’ve installed the app.

    For LG TV:
    - Ask if they have an IPTV app player (e.g., Tivimate, IBO Pro Player, or Smarters Pro).
    - If not, share this link to install the IBO Pro Player: https://us.lgappstv.com/main/tvapp/detail?appId=1209143.
    - Confirm when they’ve installed the app.

    For Roku:
    - Ask if they have an IPTV app player (e.g., Tivimate, IBO Pro Player, or Smarters Pro).
    - If not, share this link to install the IBO Pro Player: https://channelstore.roku.com/details/11b5250d70e6ec61bf516bb30bec398f/ibo-player-pro.
    - Confirm when they’ve installed the app.

    For Microsoft Devices:
    - Ask if they have an IPTV app player (e.g., Tivimate, IBO Pro Player, or Smarters Pro).
    - If not, share this link to install the IBO Pro Player: https://apps.microsoft.com/detail/9msnk97xpvrk?rtc=1&hl=fr-FR&gl=MA.
    - Confirm when they’ve installed the app.

    For Samsung TVs:
    - Ask if they have an IPTV app player (e.g., Tivimate, IBO Pro Player, or Smarters Pro).
    - If not, share this link to install the IBO Pro Player: https://shorturl.at/nAOUY.
    - Confirm when they’ve installed the app.

    IMPORTANT:
    Final Steps:
    - After the app is installed, ask for the customer’s email to send login details securely.
    - Let the customer know that the trial login credentials will be sent within a few minutes to a few hours, as soon as possible.
    - Thank the customer for choosing our service and let them know they can reach out anytime for further assistance.

    ---";

    public function generateResponse($message, $conversationId)
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
                'temperature' => 0.7,
            ]);
            $aiResponse = $response->choices[0]->message->content;

            return $aiResponse;
        } catch (\Exception $e) {
            // Log::error('OpenAI API error: ' . $e->getMessage());
            return "I apologize, but I'm having trouble processing your request at the moment. Please try again later.";
        }
    }
}
