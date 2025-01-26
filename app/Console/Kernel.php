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

    protected function schedule(Schedule $schedule)
    {
        // Run every second to check for messages to process
        $schedule->command('messages:process')->everySecond();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
