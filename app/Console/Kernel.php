<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ProcessTrialRemindersJob;

class Kernel extends ConsoleKernel
{
    protected $commands = [];

    protected function schedule(Schedule $schedule): void
    {
        // Process trial reminders every 5 minutes
        $schedule->call(function () {
            ProcessTrialRemindersJob::dispatch();
        })->everyFiveMinutes()
          ->name('process_trial_reminders')
          ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
