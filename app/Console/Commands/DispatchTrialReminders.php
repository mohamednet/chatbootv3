<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessTrialRemindersJob;

class DispatchTrialReminders extends Command
{
    protected $signature = 'reminders:dispatch';
    protected $description = 'Dispatch the trial reminders job';

    public function handle()
    {
        ProcessTrialRemindersJob::dispatch();
        $this->info('Trial reminders job dispatched successfully');
    }
}
