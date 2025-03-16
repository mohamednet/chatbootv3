<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class IboProAddPlaylistService
{
    
    public function analyzeImage($imageUrl)
    {

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
          
            'messages' => [
                    [
                        'role' => 'system',
                        'content' => "extract mac adress and device key and app  status 
                        
                        CRITICAL - YOU MUST FORMAT YOUR ENTIRE RESPONSE AS JSON:
                        You must ALWAYS return your COMPLETE response in this exact JSON format, with no additional text before or after so I can work on it in the backend:
                         {
                            mac: null,
                            key: null,
                            status: null
                         }"
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->extractData($imageUrl)
                    ]
                ],
            'temperature' => 0.2, // Lower temperature for more consistent data extraction
        ]);

        return [
            'description' => json_decode($response->choices[0]->message->content, true)
        ];

    }

    public function extractData($imageUrl)
    {
        
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "describe the image in detail with all text and numbers mac address and device key and app status and everything"
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]] // ✅ Fixed format
                        ]
                    ]
                ],
                'max_tokens' => 300
            ]);

            return $response->choices[0]->message->content;

        } catch (\Exception $e) {
            return  'Error analyzing image: ' . $e->getMessage();
            
        }
    }
}
