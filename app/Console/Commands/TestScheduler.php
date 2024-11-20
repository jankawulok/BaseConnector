<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestScheduler extends Command
{
    protected $signature = 'scheduler:test';
    protected $description = 'Test if the scheduler is running';

    public function handle()
    {
        \Log::info('Scheduler test command ran at: ' . now());
        $this->info('Scheduler test successful');
    }
}
