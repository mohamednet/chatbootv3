<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\DispatchTrialReminders;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        DispatchTrialReminders::class
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('reminders:dispatch')
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/scheduler.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
