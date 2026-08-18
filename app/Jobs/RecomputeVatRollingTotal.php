<?php

namespace App\Jobs;

use App\Finance\VatMonitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecomputeVatRollingTotal implements ShouldQueue
{
    use Queueable;

    public function handle(VatMonitor $monitor): void
    {
        $monitor->recompute();
    }
}
