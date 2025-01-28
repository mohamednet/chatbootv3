<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Message;
use App\Models\Customer;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'facebook_user_id',
        'user_name',
        'is_active',
        'response_mode',
        'last_message_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_message_at' => 'datetime'
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }
}
