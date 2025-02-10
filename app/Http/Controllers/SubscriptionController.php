<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscribers = Customer::whereNotNull('paid_status')
            ->with('conversation') // Eager load conversations
            ->get()
            ->map(function ($subscriber) {
                return [
                    'facebook_id' => $subscriber->facebook_id,
                    'email' => $subscriber->email,
                    'subscription_type' => $subscriber->subscription_type,
                    'subscription_id' => $subscriber->subscription_id,
                    'number_of_devices' => $subscriber->number_of_devices,
                    'subscription_end_date' => $subscriber->subscription_end_date,
                    'last_payment_date' => $subscriber->last_payment_date,
                    'plan' => $subscriber->plan,
                    'amount' => $subscriber->amount,
                    'payment_method' => $subscriber->payment_method,
                    'conversation_id' => $subscriber->conversation?->id,
                    'last_message' => $subscriber->conversation?->messages()->latest()->first()?->content
                ];
            });

        return view('subscriptions.index', compact('subscribers'));
    }
}
