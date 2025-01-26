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
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
