<?php

use App\Enums\CohortStatus;
use App\Enums\DelivererType;
use App\Enums\SettlementStatus;
use App\Finance\JournalPoster;
use App\Finance\Money;
use App\Finance\SettlementService;
use App\Models\Account;
use App\Models\Cohort;
use App\Models\DistributionPolicy;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\DistributionPolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->hamad = User::factory()->create()->partner()->create([
        'display_name_ar' => 'حمد', 'bio_ar' => 'شريك', 'ownership_percent' => 50,
        'effective_from' => now()->toDateString(),
    ]);
    $this->ammar = User::factory()->create()->partner()->create([
        'display_name_ar' => 'عمار', 'bio_ar' => 'شريك', 'ownership_percent' => 50,
        'effective_from' => now()->toDateString(),
    ]);

    $this->seed(ChartOfAccountsSeeder::class);
    $this->seed(DistributionPolicySeeder::class);
    $this->user = User::factory()->create();
    $this->service = app(SettlementService::class);
    $this->poster = app(JournalPoster::class);
});

function monthCohort(int $revenue, int $day = 10): Cohort
{
    $cohort = Cohort::factory()->create([
        'distribution_policy_id' => DistributionPolicy::query()->where('code', 'OMRAN_ORGANIZED')->first()->id,
        'starts_at' => now()->startOfMonth()->addDays($day - 5),
        'ends_at' => now()->startOfMonth()->addDays($day),
    ]);
    $cohort->forceFill(['status' => CohortStatus::Delivered])->save();

    $cohort->deliverers()->create([
        'deliverer_type' => DelivererType::Partner,
        'partner_id' => test()->hamad->id,
        'share_weight' => 100,
    ]);

    test()->poster->post(now(), 'إيراد', [
        ['account_id' => Account::byCode('1100')->id, 'debit' => new Money($revenue)],
        ['account_id' => Account::byCode('4010')->id, 'credit' => new Money($revenue)],
    ], test()->user->id, cohortId: $cohort->id);

    return $cohort;
}

it('computes the §8.6 monthly settlement: center pool minus period opex, split 50/50', function () {
    // دفعتان: صافي 1,000 → حصة مركز 300 لكل... مجموع حصص المركز 600
    $first = monthCohort(1000000, 8);
    $second = monthCohort(1000000, 20);

    // مصروفات تشغيلية للفترة: 42.500
    $this->poster->post(now()->startOfMonth()->addDays(12), 'اشتراكات', [
        ['account_id' => Account::byCode('6010')->id, 'debit' => new Money(42500)],
        ['account_id' => Account::byCode('1020')->id, 'credit' => new Money(42500)],
    ], $this->user->id);

    $settlement = $this->service->computeMonthlyDraft(now()->year, now()->month);

    // المثال الحرفي من §8.6: 600.000 − 42.500 = 557.500 → 278.750 لكل شريك
    expect($settlement->center_share_baisa->baisa)->toBe(600000)
        ->and($settlement->center_opex_allocated_baisa->baisa)->toBe(42500)
        ->and($settlement->distributable_profit_baisa->baisa)->toBe(557500)
        ->and($settlement->snapshot['center_allocations'][0]['amount_baisa'])->toBe(278750)
        ->and($settlement->snapshot['center_allocations'][1]['amount_baisa'])->toBe(278750);

    $settlement = $this->service->confirmMonthly($settlement, $this->user->id);

    expect($settlement->status)->toBe(SettlementStatus::Posted)
        ->and($first->refresh()->status)->toBe(CohortStatus::Settled)
        ->and($second->refresh()->status)->toBe(CohortStatus::Settled);

    // القيد متوازن
    $lines = $settlement->journalEntry->lines()->get();
    expect($lines->sum(fn ($l) => $l->debit_baisa->baisa))
        ->toBe($lines->sum(fn ($l) => $l->credit_baisa->baisa));

    // حمد: 700+700 تنفيذ + 278.750 من الوعاء
    $hamadCurrent = Account::query()->where('partner_id', $this->hamad->id)->where('code', 'like', '302%')->first();
    expect($lines->where('account_id', $hamadCurrent->id)->sum(fn ($l) => $l->credit_baisa->baisa))
        ->toBe(700000 + 700000 + 278750);
});

it('respects the opex_charged_to_center_pool toggle', function () {
    monthCohort(1000000);

    $this->poster->post(now()->startOfMonth()->addDays(3), 'اشتراكات', [
        ['account_id' => Account::byCode('6010')->id, 'debit' => new Money(50000)],
        ['account_id' => Account::byCode('1020')->id, 'credit' => new Money(50000)],
    ], $this->user->id);

    Setting::put('opex_charged_to_center_pool', false);

    $settlement = $this->service->computeMonthlyDraft(now()->year, now()->month);

    expect($settlement->center_opex_allocated_baisa->baisa)->toBe(0)
        ->and($settlement->distributable_profit_baisa->baisa)->toBe(300000);
});

it('refuses a monthly draft when no cohorts are ready', function () {
    expect(fn () => $this->service->computeMonthlyDraft(now()->year, now()->month))
        ->toThrow(DomainException::class);
});
