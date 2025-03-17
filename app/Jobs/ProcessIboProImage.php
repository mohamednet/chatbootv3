<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Trial;
use App\Http\Controllers\ChatController;
use App\Models\Customer;

class ProcessIboProImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $imageUrl;
    protected $customerId;
    public $maxAttempts = 1;

    public function __construct($imageUrl, $customerId)
    {
        $this->imageUrl = $imageUrl;
        $this->customerId = $customerId;
    }

    public function handle()
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a specialized image analyzer for IBO Pro app screenshots. Extract the MAC address, device key, and app status.

                        CRITICAL - YOU MUST FORMAT YOUR ENTIRE RESPONSE AS JSON:
                        {
                            \"mac\": \"MAC_ADDRESS_HERE or null if not found\",
                            \"key\": \"DEVICE_KEY_HERE or null if not found\",
                            \"status\": \"APP_STATUS_HERE or null if not found\"
                        }"
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->extractData($this->imageUrl)
                    ]
                ],
                'temperature' => 0.1,
                'max_tokens' => 500
            ]);

            $deviceandmac = json_decode($response->choices[0]->message->content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error: ' . json_last_error_msg());
                return ['error' => 'Failed to parse image analysis results'];
            }

            if (!empty($deviceandmac['mac']) && !empty($deviceandmac['key'])) {
                Log::info('Device info extracted', ['mac' => $deviceandmac['mac'], 'key' => $deviceandmac['key']]);
                
                try {
                    $trial = Trial::where('assigned_user', $this->customerId)->first();
                    
                    if ($trial) {
                        Log::info('Found trial for customer', ['customer_id' => $this->customerId, 'trial_id' => $trial->id]);
                        $deviceandmac['m3u_link'] = $trial->m3u_link;
                        
                        // Add playlist without triggering webhook loop
                        $this->addPlaylist($deviceandmac['mac'], $deviceandmac['key'], $deviceandmac['m3u_link'], $this->customerId);
                    } else {
                        Log::info('No trial found for customer', ['customer_id' => $this->customerId]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing trial: ' . $e->getMessage(), ['customer_id' => $this->customerId]);
                }
            } else {
                Log::warning('Missing required device info', ['mac_found' => !empty($deviceandmac['mac']), 'key_found' => !empty($deviceandmac['key'])]);
            }

            return [
                'mac' => $deviceandmac['mac'] ?? null,
                'key' => $deviceandmac['key'] ?? null,
                'status' => $deviceandmac['status'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Image analysis failed: ' . $e->getMessage(), ['customer_id' => $this->customerId]);
            return ['error' => 'Failed to analyze image: ' . $e->getMessage()];
        }
    }

    private function addPlaylist($macAddress, $deviceKey, $m3uLink, $customerId)
    {
        try {
            $response = Http::timeout(60)->post('http://167.88.165.54:8080/add_playlist', [
                'mac_address' => $macAddress,
                'device_key' => $deviceKey,
                'playlist_name' => 'PrimeVision',
                'playlist_url' => $m3uLink
            ]);

            $result = $response->json();
            
            if (isset($result['success']) && $result['success'] === true) {
                Log::info('Playlist added successfully', [
                    'customer_id' => $customerId,
                    'mac' => $macAddress
                ]);
                //here send a message via facebook to customer with this ""Could you please reload your app? Your playlist has been added successfully."
                try {
                    //update custers ibopro_mac_address ibopro_device_key ibopro_credentials_status to true
                    $customer = Customer::find($customerId);
                    $customer->ibopro_mac_address = $macAddress;
                    $customer->ibopro_device_key = $deviceKey;
                    $customer->save();

                    $chatcontoller = new ChatController();
                    $chatcontoller->sendFacebookMessage($customerId, 'Could you please reload your app? Your playlist has been added successfully.');
                } catch (\Exception $e) {
                    Log::error('Failed to send Facebook message: ' . $e->getMessage(), [
                        'customer_id' => $customerId,
                        'mac' => $macAddress
                    ]);
                }
                return true;
            } else {
                Log::error('Failed to add playlist', [
                    'customer_id' => $customerId,
                    'mac' => $macAddress,
                    'response' => $result
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error adding playlist: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'mac' => $macAddress
            ]);
            return false;
        }
    }

    private function extractData($imageUrl)
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "describe the image in detail with all text and numbers mac address and device key and app status and everything"
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]]
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
