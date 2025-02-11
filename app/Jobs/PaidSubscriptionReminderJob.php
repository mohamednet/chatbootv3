<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\FacebookService;
use App\Services\MessageTemplateService;
use App\Mail\TrialReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaidSubscriptionReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(FacebookService $facebookService)
    {
        try {
            // First reminder: 7 days before expiry
            $sevenDaysCustomers = Customer::query()
                ->where('paid_status', true)
                ->where('subscription_end_date', '>', now())
                ->where('subscription_end_date', '<=', now()->addDays(7))
                ->where('reminder_count_paid', 0)
                ->get();

            foreach ($sevenDaysCustomers as $customer) {
                // Send Facebook message
                $facebookService->sendMessage(
                    $customer->facebook_id,
                    MessageTemplateService::getPaidTemplate('first', 'facebook')
                );

                // Send Email
                if ($customer->email) {
                    Mail::to($customer->email)->send(new TrialReminder(
                        MessageTemplateService::getPaidTemplate('first', 'email_subject'),
                        MessageTemplateService::getPaidTemplate('first', 'email_content')
                    ));
                }

                $customer->increment('reminder_count_paid');
                Log::info('Sent 7-day subscription reminder', ['customer_id' => $customer->id]);
            }

            // Second reminder: 2 days before expiry
            $twoDaysCustomers = Customer::query()
                ->where('paid_status', true)
                ->where('subscription_end_date', '>', now())
                ->where('subscription_end_date', '<=', now()->addDays(2))
                ->where('reminder_count_paid', 1)
                ->get();

            foreach ($twoDaysCustomers as $customer) {
                // Send Facebook message
                $facebookService->sendMessage(
                    $customer->facebook_id,
                    MessageTemplateService::getPaidTemplate('second', 'facebook')
                );

                // Send Email
                if ($customer->email) {
                    Mail::to($customer->email)->send(new TrialReminder(
                        MessageTemplateService::getPaidTemplate('second', 'email_subject'),
                        MessageTemplateService::getPaidTemplate('second', 'email_content')
                    ));
                }

                $customer->increment('reminder_count_paid');
                Log::info('Sent 2-day subscription reminder', ['customer_id' => $customer->id]);
            }

            // Third reminder: On expiry
            $expiredCustomers = Customer::query()
                ->where('paid_status', true)
                ->where('subscription_end_date', '<=', now())
                ->where('reminder_count_paid', 2)
                ->get();

            foreach ($expiredCustomers as $customer) {
                // Send Facebook message
                $facebookService->sendMessage(
                    $customer->facebook_id,
                    MessageTemplateService::getPaidTemplate('third', 'facebook')
                );

                // Send Email
                if ($customer->email) {
                    Mail::to($customer->email)->send(new TrialReminder(
                        MessageTemplateService::getPaidTemplate('third', 'email_subject'),
                        MessageTemplateService::getPaidTemplate('third', 'email_content')
                    ));
                }

                $customer->increment('reminder_count_paid');
                $customer->update(['paid_status' => false]);
                Log::info('Sent expired subscription reminder', ['customer_id' => $customer->id]);
            }
        } catch (\Exception $e) {
            Log::error('Error in PaidSubscriptionReminderJob', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
