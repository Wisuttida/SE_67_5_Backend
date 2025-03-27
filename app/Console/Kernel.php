<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Schedule; // Import the Schedule class

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\HashUserPassword::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Schedule commands here
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
    
}
