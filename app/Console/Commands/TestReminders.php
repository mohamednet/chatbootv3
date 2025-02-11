<?php

namespace App\Console\Commands;

use App\Services\FacebookService;
use App\Services\MessageTemplateService;
use App\Models\Customer;
use App\Models\Trial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrialReminder;

class TestReminders extends Command
{
    protected $signature = 'test:reminders';
    protected $description = 'Send test messages of all types';

    private $testFacebookId = '8818996418151271';
    private $testEmail = 'mohamed.anattahe@gmail.com';

    public function handle(FacebookService $facebookService, MessageTemplateService $messageTemplateService)
    {
        $this->info('Sending test messages...');

        // Create or update test customer
        $customer = Customer::updateOrCreate(
            ['facebook_id' => $this->testFacebookId],
            [
                'email' => $this->testEmail,
                'reminder_count_trial' => 0,
                'marketing_reminder_count' => 0,
                'reminder_count_paid' => 0,
                'paid_status' => true,
                'subscription_end_date' => now()->addDays(7),
                'non_disturb' => false
            ]
        );

        // Send all trial messages
        $this->info('Sending trial reminders...');
        foreach (['first', 'second', 'third'] as $type) {
            $this->info("Sending $type trial reminder...");
            $facebookService->sendMessage(
                $this->testFacebookId,
                MessageTemplateService::getTrialTemplate($type, 'facebook')
            );
            Mail::to($this->testEmail)->send(new TrialReminder(
                MessageTemplateService::getTrialTemplate($type, 'email_subject'),
                MessageTemplateService::getTrialTemplate($type, 'email_content')
            ));
            sleep(2); // Wait 2 seconds between messages
        }

        // Send all marketing messages
        $this->info('Sending marketing messages...');
        for ($i = 1; $i <= 5; $i++) {
            $this->info("Sending marketing message $i...");
            $facebookService->sendMessage(
                $this->testFacebookId,
                MessageTemplateService::getMarketingTemplate($i)
            );
            sleep(2); // Wait 2 seconds between messages
        }

        // Send all paid subscription messages
        $this->info('Sending paid subscription reminders...');
        foreach (['first', 'second', 'third'] as $type) {
            $this->info("Sending $type subscription reminder...");
            $facebookService->sendMessage(
                $this->testFacebookId,
                MessageTemplateService::getPaidTemplate($type, 'facebook')
            );
            Mail::to($this->testEmail)->send(new TrialReminder(
                MessageTemplateService::getPaidTemplate($type, 'email_subject'),
                MessageTemplateService::getPaidTemplate($type, 'email_content')
            ));
            sleep(2); // Wait 2 seconds between messages
        }

        $this->info('All test messages sent!');
        return 0;
    }
}
