<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ProcessTrialReminders::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Schedule is handled by external PowerShell script
        $schedule->job(new Commands\ProcessTrialReminders())
        ->everyFiveMinutes()
        ->appendOutputTo(storage_path('logs/trial-reminders.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
