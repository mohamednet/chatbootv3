<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use Illuminate\Console\Command;

class CheckConversation extends Command
{
    protected $signature = 'check:conversation {facebook_user_id}';
    protected $description = 'Check conversation details for a Facebook user';

    public function handle()
    {
        $facebookUserId = $this->argument('facebook_user_id');
        
        $conversation = Conversation::where('facebook_user_id', $facebookUserId)->first();
        
        if (!$conversation) {
            $this->error("No conversation found for Facebook user ID: {$facebookUserId}");
            return 1;
        }

        $this->info("Conversation Details:");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $conversation->id],
                ['Facebook User ID', $conversation->facebook_user_id],
                ['Customer Name', $conversation->customer_name ?? 'NULL'],
                ['Response Mode', $conversation->response_mode],
                ['Created At', $conversation->created_at],
                ['Updated At', $conversation->updated_at],
            ]
        );

        return 0;
    }
}
