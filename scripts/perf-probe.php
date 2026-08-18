<?php

use App\Finance\DashboardMetrics;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\TaxSettingsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// قاعدة مؤقتة للقياس فقط — spec Phase 6: dashboard < 400ms @ 5,000 قيد
if (DB::connection()->getDatabaseName() !== 'omran_perf') {
    echo "refusing: not on omran_perf\n";

    return;
}

if (User::query()->count() === 0) {
    $hamad = User::factory()->create();
    $ammar = User::factory()->create();

    $hamad->partner()->create([
        'display_name_ar' => 'حمد', 'bio_ar' => 'شريك', 'ownership_percent' => 50,
        'effective_from' => now()->toDateString(),
    ]);
    $ammar->partner()->create([
        'display_name_ar' => 'عمار', 'bio_ar' => 'شريك', 'ownership_percent' => 50,
        'effective_from' => now()->toDateString(),
    ]);

    (new ChartOfAccountsSeeder)->run();
    (new TaxSettingsSeeder)->run();
}

if (DB::table('journal_entries')->count() < 5000) {
    Artisan::call('app:seed-demo-journal', ['count' => 5000]);
    echo trim(Artisan::output())."\n";
}

echo 'entries: '.DB::table('journal_entries')->count()."\n";
echo 'lines: '.DB::table('journal_lines')->count()."\n";

$metrics = app(DashboardMetrics::class);

foreach (range(1, 5) as $round) {
    $start = hrtime(true);
    $built = $metrics->build();
    $ms = (hrtime(true) - $start) / 1_000_000;

    printf("round %d: %.1f ms (cash %s, vat %s)\n", $round, $ms, $built['cash_total']->toDecimalString(), $built['vat']['state']);
}
