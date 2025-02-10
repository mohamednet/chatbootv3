<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ProcessMessages::class,
        Commands\TestOpenAI::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Run every second to check for messages to process
        $schedule->command('messages:process')->everySecond();

        // Reminder Jobs
        $schedule->job(new TrialReminderJob)->hourly();
        $schedule->job(new MarketingReminderJob)->hourly();
        $schedule->job(new PaidSubscriptionReminderJob)->hourly();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
