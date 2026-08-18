<?php

use App\Finance\DashboardMetrics;
use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds the dashboard metrics under 400ms with 5,000 journal entries', function () {
    User::factory()->create()->partner()->create([
        'display_name_ar' => 'حمد', 'bio_ar' => 'شريك', 'ownership_percent' => 50,
        'effective_from' => now()->toDateString(),
    ]);
    $this->seed(ChartOfAccountsSeeder::class);

    $this->artisan('app:seed-demo-journal', ['count' => 5000])->assertSuccessful();

    expect(JournalEntry::query()->count())->toBe(5000);

    $metrics = app(DashboardMetrics::class); // resolve outside the clock

    $start = hrtime(true);
    $built = $metrics->build();
    $elapsedMs = (hrtime(true) - $start) / 1_000_000;

    // الميزانية الحقيقية تُقاس على MySQL في Docker؛ هذا حارس تراجع
    expect($elapsedMs)->toBeLessThan(400.0)
        ->and($built['cash_total']->baisa)->not->toBe(0);
});
