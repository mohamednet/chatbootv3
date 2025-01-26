<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchCustomerName extends Command
{
    protected $signature = 'fetch:customer-name {facebook_user_id}';
    protected $description = 'Fetch customer name from Facebook';

    public function handle()
    {
        $facebookUserId = $this->argument('facebook_user_id');
        $conversation = Conversation::where('facebook_user_id', $facebookUserId)->first();
        
        if (!$conversation) {
            $this->error("No conversation found for Facebook user ID: {$facebookUserId}");
            return 1;
        }

        $this->info("Fetching name for user ID: {$facebookUserId}");
        
        try {
            $url = "https://graph.facebook.com/v18.0/{$facebookUserId}";
            $token = config('services.facebook.page_access_token');
            
            $this->info("Making request to: {$url}");
            
            $response = Http::get($url, [
                'fields' => 'name',
                'access_token' => $token
            ]);

            $this->info("Response status: " . $response->status());
            $this->info("Response body: " . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['name'])) {
                    $conversation->update(['customer_name' => $data['name']]);
                    $this->info("Successfully updated customer name to: {$data['name']}");
                } else {
                    $this->error("Name field not found in response");
                }
            } else {
                $this->error("Failed to fetch name from Facebook");
                $this->error("Error: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error('Error in fetch:customer-name command: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
