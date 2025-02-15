<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

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

        // Run trial reminders every 5 minutes
        $schedule->call(function () {
            Log::info('Scheduling TrialReminderJob');
            dispatch(new \App\Jobs\TrialReminderJob());
        })->everyFiveMinutes();

        // Run marketing reminders every hour
        $schedule->call(function () {
            Log::info('Scheduling MarketingReminderJob');
            dispatch(new \App\Jobs\MarketingReminderJob());
        })->hourly();

        // Run paid subscription reminders every hour
        $schedule->job(new \App\Jobs\PaidSubscriptionReminderJob)->hourly();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
