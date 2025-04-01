<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ProcessTrialReminders::class,
        Commands\RunTrialReminders::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('run:trial-reminders')
                ->everyFiveMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
