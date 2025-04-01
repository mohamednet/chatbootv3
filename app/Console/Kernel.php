<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ProcessTrialReminders;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        ProcessTrialReminders::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(ProcessTrialReminders::class)
                ->everyFiveMinutes()
                ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
