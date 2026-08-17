<?php

/**
 * The eight golden cases from spec §8.8 — written before the engine (spec
 * §14) and locked to exact baisa values from the partnership contract.
 * These must never be "improved".
 */

use App\Enums\DelivererType;
use App\Finance\DistributionEngine;
use App\Finance\JournalPoster;
use App\Finance\Money;
use App\Models\Account;
use App\Models\Cohort;
use App\Models\DistributionPolicy;
use App\Models\Instructor;
use App\Models\Partner;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\DistributionPolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // الشريكان بالترتيب التعاقدي: حمد ثم عمار — 50/50
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
    $this->poster = app(JournalPoster::class);
    $this->engine = app(DistributionEngine::class);
});

/**
 * دفعة جاهزة للاختبار: سياسة + منفذون + إيراد + تكاليف مباشرة
 *
 * @param  list<array{partner?: Partner, instructor?: Instructor, weight: float|int}>  $deliverers
 */
function goldenCohort(string $policyCode, array $deliverers, int $revenueBaisa, int $adsBaisa = 0, ?int $externalFeeBaisa = null): Cohort
{
    $cohort = Cohort::factory()->create([
        'distribution_policy_id' => DistributionPolicy::query()->where('code', $policyCode)->firstOrFail()->id,
        'external_fee_baisa' => $externalFeeBaisa,
    ]);

    foreach ($deliverers as $deliverer) {
        $cohort->deliverers()->create([
            'deliverer_type' => isset($deliverer['partner']) ? DelivererType::Partner : DelivererType::External,
            'partner_id' => $deliverer['partner']->id ?? null,
            'instructor_id' => $deliverer['instructor']->id ?? null,
            'share_weight' => $deliverer['weight'],
        ]);
    }

    $poster = app(JournalPoster::class);
    $user = User::query()->latest('id')->first();

    if ($revenueBaisa > 0) {
        $poster->post(now(), 'إيراد الدفعة', [
            ['account_id' => Account::byCode('1100')->id, 'debit' => new Money($revenueBaisa)],
            ['account_id' => Account::byCode('4010')->id, 'credit' => new Money($revenueBaisa)],
        ], $user->id, cohortId: $cohort->id);
    }

    if ($adsBaisa > 0) {
        $poster->post(now(), 'إعلانات مأجورة', [
            ['account_id' => Account::byCode('5030')->id, 'debit' => new Money($adsBaisa)],
            ['account_id' => Account::byCode('1020')->id, 'credit' => new Money($adsBaisa)],
        ], $user->id, cohortId: $cohort->id);
    }

    return $cohort;
}

function allocationOfPartner($result, Partner $partner): int
{
    return collect($result->delivererAllocations)
        ->where('partner_id', $partner->id)
        ->sum(fn ($allocation) => $allocation['amount']->baisa);
}

function centerAllocationOfPartner($result, Partner $partner): int
{
    return collect($result->centerAllocations)
        ->where('partner_id', $partner->id)
        ->sum(fn ($allocation) => $allocation['amount']->baisa);
}

test('golden 1 — external invitation, no direct costs (وزارة الأوقاف)', function () {
    $cohort = goldenCohort('EXTERNAL_INVITATION', [['partner' => $this->ammar, 'weight' => 100]], 640000);

    $result = $this->engine->compute($cohort);

    expect($result->grossRevenue->baisa)->toBe(640000)
        ->and($result->directCosts->baisa)->toBe(0)
        ->and($result->netDistributable->baisa)->toBe(640000)
        ->and(allocationOfPartner($result, $this->ammar))->toBe(512000)
        ->and($result->centerShare->baisa)->toBe(128000)
        ->and(centerAllocationOfPartner($result, $this->hamad))->toBe(64000)
        ->and(centerAllocationOfPartner($result, $this->ammar))->toBe(64000)
        ->and(allocationOfPartner($result, $this->ammar) + centerAllocationOfPartner($result, $this->ammar))->toBe(576000)
        ->and($result->totalAllocated())->toBe(640000)
        ->and($result->flags)->toBe([]);
});

test('golden 2 — Omran-organized with advertising', function () {
    $cohort = goldenCohort('OMRAN_ORGANIZED', [['partner' => $this->hamad, 'weight' => 100]], 1000000, adsBaisa: 150000);

    $result = $this->engine->compute($cohort);

    expect($result->netDistributable->baisa)->toBe(850000)
        ->and(allocationOfPartner($result, $this->hamad))->toBe(595000)
        ->and($result->centerShare->baisa)->toBe(255000)
        ->and(centerAllocationOfPartner($result, $this->hamad))->toBe(127500)
        ->and(centerAllocationOfPartner($result, $this->ammar))->toBe(127500)
        ->and(allocationOfPartner($result, $this->hamad) + centerAllocationOfPartner($result, $this->hamad))->toBe(722500)
        ->and($result->totalAllocated())->toBe(850000)
        ->and($result->flags)->toBe([]);
});

test('golden 3 — external trainer, fixed fee', function () {
    $instructor = Instructor::factory()->create();
    $cohort = goldenCohort('EXTERNAL_TRAINER', [['instructor' => $instructor, 'weight' => 100]], 2000000, adsBaisa: 300000, externalFeeBaisa: 800000);

    $result = $this->engine->compute($cohort);

    $trainerShare = collect($result->delivererAllocations)
        ->where('instructor_id', $instructor->id)
        ->sum(fn ($allocation) => $allocation['amount']->baisa);

    expect($result->netDistributable->baisa)->toBe(1700000)
        ->and($trainerShare)->toBe(800000)
        ->and($result->centerShare->baisa)->toBe(900000)
        ->and(centerAllocationOfPartner($result, $this->hamad))->toBe(450000)
        ->and(centerAllocationOfPartner($result, $this->ammar))->toBe(450000)
        ->and($result->totalAllocated())->toBe(1700000)
        ->and($result->flags)->toBe([]);
});

test('golden 4 — rounding: no baisa lost, none invented', function () {
    $cohort = goldenCohort('OMRAN_ORGANIZED', [['partner' => $this->hamad, 'weight' => 100]], 333333);

    $result = $this->engine->compute($cohort);

    expect($result->netDistributable->baisa)->toBe(333333)
        ->and(allocationOfPartner($result, $this->hamad))->toBe(233333)
        ->and($result->centerShare->baisa)->toBe(100000)
        ->and(centerAllocationOfPartner($result, $this->hamad))->toBe(50000)
        ->and(centerAllocationOfPartner($result, $this->ammar))->toBe(50000)
        ->and($result->totalAllocated())->toBe(333333);
});

test('golden 5 — loss: deliverer zero, center takes the loss, settlement flagged', function () {
    $cohort = goldenCohort('OMRAN_ORGANIZED', [['partner' => $this->hamad, 'weight' => 100]], 200000, adsBaisa: 300000);

    $result = $this->engine->compute($cohort);

    expect($result->netDistributable->baisa)->toBe(-100000)
        ->and($result->delivererTotal->baisa)->toBe(0)
        ->and($result->centerShare->baisa)->toBe(-100000)
        ->and(centerAllocationOfPartner($result, $this->hamad))->toBe(-50000)
        ->and(centerAllocationOfPartner($result, $this->ammar))->toBe(-50000)
        ->and($result->flags)->toContain('LOSS');
});

test('golden 6 — co-delivery by weight', function () {
    $cohort = goldenCohort('OMRAN_ORGANIZED', [
        ['partner' => $this->hamad, 'weight' => 60],
        ['partner' => $this->ammar, 'weight' => 40],
    ], 1000000);

    $result = $this->engine->compute($cohort);

    expect($result->delivererTotal->baisa)->toBe(700000)
        ->and(allocationOfPartner($result, $this->hamad))->toBe(420000)
        ->and(allocationOfPartner($result, $this->ammar))->toBe(280000)
        ->and($result->centerShare->baisa)->toBe(300000)
        ->and(centerAllocationOfPartner($result, $this->hamad))->toBe(150000)
        ->and(centerAllocationOfPartner($result, $this->ammar))->toBe(150000)
        ->and(allocationOfPartner($result, $this->hamad) + centerAllocationOfPartner($result, $this->hamad))->toBe(570000)
        ->and(allocationOfPartner($result, $this->ammar) + centerAllocationOfPartner($result, $this->ammar))->toBe(430000)
        ->and($result->totalAllocated())->toBe(1000000);
});

test('golden 7 — overcommitted fixed fee blocks settlement', function () {
    $instructor = Instructor::factory()->create();
    $cohort = goldenCohort('EXTERNAL_TRAINER', [['instructor' => $instructor, 'weight' => 100]], 500000, externalFeeBaisa: 800000);

    $result = $this->engine->compute($cohort);

    expect($result->flags)->toContain('OVERCOMMITTED')
        ->and($result->centerShare->baisa)->toBe(-300000);
});

test('property — 10,000 random combinations always allocate exactly', function () {
    mt_srand(20260817);
    $engine = $this->engine;

    // نفحص جوهر التوزيع (القسمة المزدوجة منفذ/مركز ثم الشركاء) بمعزل عن
    // قاعدة البيانات — نفس المسار الحسابي الذي يستخدمه المحرك
    foreach (range(1, 10000) as $i) {
        $net = mt_rand(-1_000_000, 50_000_000);
        $percent = [80, 70, mt_rand(1, 99)][$i % 3];
        $weightHamad = mt_rand(1, 99);

        $money = new Money($net);

        if ($net <= 0) {
            $shares = $money->allocate(['hamad' => 50, 'ammar' => 50]);
            expect($shares['hamad']->baisa + $shares['ammar']->baisa)->toBe($net);

            continue;
        }

        $split = $money->allocate(['deliverer' => $percent, 'center' => 100 - $percent]);
        $delivererShares = $split['deliverer']->allocate(['a' => $weightHamad, 'b' => 100 - $weightHamad]);
        $centerShares = $split['center']->allocate(['hamad' => 50, 'ammar' => 50]);

        $total = $delivererShares['a']->baisa + $delivererShares['b']->baisa
            + $centerShares['hamad']->baisa + $centerShares['ammar']->baisa;

        expect($total)->toBe($net);
    }
});
