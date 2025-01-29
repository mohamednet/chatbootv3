<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Conversation;
use App\Models\User;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'content',
        'type',
        'sender_type',
        'admin_id',
        'metadata',
        'facebook_message_id'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            $message->conversation->update([
                'last_message_at' => $message->created_at
            ]);
        });
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isAttachment()
    {
        return str_starts_with($this->content, '[Attachment:');
    }

    public function parseAttachment()
    {
        if (!$this->isAttachment()) return null;
        
        // Extract the content between [Attachment: ] brackets
        preg_match('/\[Attachment: \[(.*?)\]\]/', $this->content, $matches);
        if (empty($matches[1])) return null;
        
        // Parse type and url
        $parts = explode(', ', $matches[1]);
        $type = str_replace('type: ', '', $parts[0]);
        $url = str_replace('url: ', '', str_replace("'", '', $parts[1]));
        
        return [
            'type' => $type,
            'url' => $url
        ];
    }
}
