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
use App\Models\Instructor;
use App\Models\JournalEntry;
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
});

function settlableCohort(string $policyCode, array $deliverers, int $revenue, int $ads = 0, ?int $fee = null): Cohort
{
    $cohort = Cohort::factory()->create([
        'distribution_policy_id' => DistributionPolicy::query()->where('code', $policyCode)->firstOrFail()->id,
        'external_fee_baisa' => $fee,
    ]);
    $cohort->forceFill(['status' => CohortStatus::Delivered])->save();

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

    if ($revenue > 0) {
        $poster->post(now(), 'إيراد', [
            ['account_id' => Account::byCode('1100')->id, 'debit' => new Money($revenue)],
            ['account_id' => Account::byCode('4010')->id, 'credit' => new Money($revenue)],
        ], $user->id, cohortId: $cohort->id);
    }

    if ($ads > 0) {
        $poster->post(now(), 'إعلانات', [
            ['account_id' => Account::byCode('5030')->id, 'debit' => new Money($ads)],
            ['account_id' => Account::byCode('1020')->id, 'credit' => new Money($ads)],
        ], $user->id, cohortId: $cohort->id);
    }

    return $cohort;
}

function entryBalances(JournalEntry $entry): bool
{
    $lines = $entry->lines()->get();

    return $lines->sum(fn ($line) => $line->debit_baisa->baisa)
        === $lines->sum(fn ($line) => $line->credit_baisa->baisa);
}

it('settles the وزارة الأوقاف case end to end with a balanced journal (golden 8)', function () {
    $cohort = settlableCohort('EXTERNAL_INVITATION', [['partner' => $this->ammar, 'weight' => 100]], 640000);

    $settlement = $this->service->computeCohortDraft($cohort);

    expect($settlement->status)->toBe(SettlementStatus::Draft)
        ->and($settlement->net_distributable_baisa->baisa)->toBe(640000);

    $settlement = $this->service->confirm($settlement, $this->user->id);

    expect($settlement->status)->toBe(SettlementStatus::Posted)
        ->and($settlement->journal_entry_id)->not->toBeNull()
        ->and(entryBalances($settlement->journalEntry))->toBeTrue()
        ->and($cohort->refresh()->status)->toBe(CohortStatus::Settled);

    // استحقاق عمار في حسابه الجاري = 512000 تنفيذ + 64000 من المركز
    $ammarCurrent = Account::query()->where('partner_id', $this->ammar->id)->where('code', 'like', '302%')->first();
    $credited = $settlement->journalEntry->lines()->where('account_id', $ammarCurrent->id)->get()
        ->sum(fn ($line) => $line->credit_baisa->baisa);

    expect($credited)->toBe(576000);
});

it('posts balanced settlement journals for every permitted golden case (golden 8)', function () {
    $instructor = Instructor::factory()->create();

    $cases = [
        settlableCohort('EXTERNAL_INVITATION', [['partner' => $this->ammar, 'weight' => 100]], 640000),
        settlableCohort('OMRAN_ORGANIZED', [['partner' => $this->hamad, 'weight' => 100]], 1000000, ads: 150000),
        settlableCohort('EXTERNAL_TRAINER', [['instructor' => $instructor, 'weight' => 100]], 2000000, ads: 300000, fee: 800000),
        settlableCohort('OMRAN_ORGANIZED', [['partner' => $this->hamad, 'weight' => 100]], 333333),
        settlableCohort('OMRAN_ORGANIZED', [
            ['partner' => $this->hamad, 'weight' => 60],
            ['partner' => $this->ammar, 'weight' => 40],
        ], 1000000),
    ];

    foreach ($cases as $cohort) {
        $settlement = $this->service->confirm($this->service->computeCohortDraft($cohort), $this->user->id);

        expect(entryBalances($settlement->journalEntry))->toBeTrue();
    }
});

it('blocks a loss settlement until explicitly accepted, then posts it balanced', function () {
    $cohort = settlableCohort('OMRAN_ORGANIZED', [['partner' => $this->hamad, 'weight' => 100]], 200000, ads: 300000);
    $settlement = $this->service->computeCohortDraft($cohort);

    expect(fn () => $this->service->confirm($settlement, $this->user->id))
        ->toThrow(DomainException::class, 'LOSS');

    $settlement = $this->service->confirm($settlement->refresh(), $this->user->id, acceptLoss: true);

    expect($settlement->status)->toBe(SettlementStatus::Posted)
        ->and(entryBalances($settlement->journalEntry))->toBeTrue();

    // الخسارة 100000 مقسومة على الجاريين مديناً
    $hamadCurrent = Account::query()->where('partner_id', $this->hamad->id)->where('code', 'like', '302%')->first();
    $debited = $settlement->journalEntry->lines()->where('account_id', $hamadCurrent->id)->get()
        ->sum(fn ($line) => $line->debit_baisa->baisa);

    expect($debited)->toBe(50000);
});

it('blocks an overcommitted fee until an override reason is written (golden 7 flow)', function () {
    $instructor = Instructor::factory()->create();
    $cohort = settlableCohort('EXTERNAL_TRAINER', [['instructor' => $instructor, 'weight' => 100]], 500000, fee: 800000);

    $settlement = $this->service->computeCohortDraft($cohort);

    expect(fn () => $this->service->confirm($settlement, $this->user->id))
        ->toThrow(DomainException::class, 'OVERCOMMITTED');

    $settlement = $this->service->confirm(
        $settlement->refresh(),
        $this->user->id,
        acceptLoss: true,
        overrideReasonAr: 'اتفاق مسبق مع المدرب — الفارق على حساب المركز',
    );

    expect($settlement->status)->toBe(SettlementStatus::Posted)
        ->and(entryBalances($settlement->journalEntry))->toBeTrue()
        ->and($settlement->notes_ar)->toContain('اتفاق مسبق');
});

it('freezes the snapshot: a later policy edit does not change a posted settlement', function () {
    $cohort = settlableCohort('OMRAN_ORGANIZED', [['partner' => $this->hamad, 'weight' => 100]], 1000000);
    $settlement = $this->service->confirm($this->service->computeCohortDraft($cohort), $this->user->id);

    $snapshotBefore = $settlement->snapshot;

    // مالك يعدل السياسة لاحقاً: 60% بدل 70%
    DistributionPolicy::query()->where('code', 'OMRAN_ORGANIZED')->first()
        ->update(['deliverer_share_percent' => 60, 'version' => 2]);

    $settlement->refresh();

    expect($settlement->snapshot)->toBe($snapshotBefore)
        ->and($settlement->snapshot['policy']['deliverer_share_percent'])->toBe('70.00')
        ->and($settlement->snapshot['deliverer_allocations'][0]['amount_baisa'])->toBe(700000)
        ->and($settlement->deliverer_total_baisa->baisa)->toBe(700000);
});

it('reverses a posted settlement and reopens the cohort', function () {
    $cohort = settlableCohort('EXTERNAL_INVITATION', [['partner' => $this->ammar, 'weight' => 100]], 640000);
    $settlement = $this->service->confirm($this->service->computeCohortDraft($cohort), $this->user->id);

    $settlement = $this->service->reverse($settlement, $this->user->id, 'تصحيح مبلغ الإيراد');

    expect($settlement->status)->toBe(SettlementStatus::Reversed)
        ->and($settlement->journalEntry->refresh()->status->value)->toBe('reversed')
        ->and($cohort->refresh()->status)->toBe(CohortStatus::Delivered);

    // بعد العكس يمكن التصفية من جديد
    $again = $this->service->computeCohortDraft($cohort->refresh());
    expect($again->status)->toBe(SettlementStatus::Draft);
});

it('refuses to settle a cohort that is not delivered', function () {
    $cohort = settlableCohort('EXTERNAL_INVITATION', [['partner' => $this->ammar, 'weight' => 100]], 640000);
    $cohort->forceFill(['status' => CohortStatus::Open])->save();

    expect(fn () => $this->service->computeCohortDraft($cohort->refresh()))
        ->toThrow(DomainException::class);
});
