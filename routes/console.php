<?php

use App\Jobs\RecomputeVatRollingTotal;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// spec §8.9: مراقب حد القيمة المضافة يُعاد حسابه ليلياً
Schedule::job(new RecomputeVatRollingTotal)->dailyAt('02:00');
