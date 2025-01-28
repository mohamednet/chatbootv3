<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Conversation;
use App\Models\Message;

return new class extends Migration
{
    public function up()
    {
        // Update last_message_at for all conversations based on their latest message
        $conversations = Conversation::all();
        foreach ($conversations as $conversation) {
            $latestMessage = Message::where('conversation_id', $conversation->id)
                ->latest()
                ->first();
            
            if ($latestMessage) {
                $conversation->update([
                    'last_message_at' => $latestMessage->created_at
                ]);
            }
        }
    }

    public function down()
    {
        // No need for down migration as this is just data update
    }
};
