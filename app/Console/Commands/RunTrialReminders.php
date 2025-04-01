<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunTrialReminders extends Command
{
    protected $signature = 'run:trial-reminders';
    protected $description = 'Run trial reminders once';

    public function handle()
    {
        try {
            Log::info('Starting trial reminders at: ' . now());
            $this->call('trials:process-reminders');
            Log::info('Completed trial reminders at: ' . now());
            return 0;
        } catch (\Exception $e) {
            Log::error('Error running trial reminders: ' . $e->getMessage());
            return 1;
        }
    }
}
