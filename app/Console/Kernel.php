<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\UsageCounter;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            UsageCounter::where('period_end', '<', now())->delete();
        })->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
