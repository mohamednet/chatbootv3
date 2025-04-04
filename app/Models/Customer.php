<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Conversation;

class Customer extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'facebook_id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'facebook_id',
        'conversation_id',
        'email',
        'device',
        'app',
        'response_mode',
        'trial_status',
        'metadata',
        'paid_status',
        'subscription_id',
        'subscription_end_date',
        'last_payment_date',
        'subscription_type',
        'number_of_devices',
        'plan',
        'amount',
        'payment_method'
    ];

    /**
     * Get the conversation associated with the customer.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
