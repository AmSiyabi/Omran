<?php

use App\Enums\CohortStatus;
use App\Enums\DelivererType;
use App\Finance\JournalPoster;
use App\Finance\Money;
use App\Finance\SettlementService;
use App\Livewire\Admin\Finance\FinanceHub;
use App\Livewire\Admin\Finance\QuickAddExpense;
use App\Livewire\Admin\Finance\SettlementShow;
use App\Models\Account;
use App\Models\Cohort;
use App\Models\DistributionPolicy;
use App\Models\JournalEntry;
use App\Models\Partner;
use App\Models\Settlement;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\DistributionPolicySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->partner = User::factory()->create()->partner()->create([
        'display_name_ar' => 'حمد', 'bio_ar' => 'شريك', 'ownership_percent' => 50,
        'effective_from' => now()->toDateString(),
    ]);
    User::factory()->create()->partner()->create([
        'display_name_ar' => 'عمار', 'bio_ar' => 'شريك', 'ownership_percent' => 50,
        'effective_from' => now()->toDateString(),
    ]);

    $this->seed(ChartOfAccountsSeeder::class);
    $this->seed(DistributionPolicySeeder::class);
});

function financeUser(string $role): User
{
    $user = User::factory()->withConfirmedTwoFactor()->create();
    $user->assignRole($role);

    return $user;
}

function draftSettlement(): Settlement
{
    $cohort = Cohort::factory()->create([
        'distribution_policy_id' => DistributionPolicy::query()->where('code', 'EXTERNAL_INVITATION')->first()->id,
    ]);
    $cohort->forceFill(['status' => CohortStatus::Delivered])->save();

    $cohort->deliverers()->create([
        'deliverer_type' => DelivererType::Partner,
        'partner_id' => Partner::query()->first()->id,
        'share_weight' => 100,
    ]);

    app(JournalPoster::class)->post(now(), 'إيراد', [
        ['account_id' => Account::byCode('1100')->id, 'debit' => new Money(640000)],
        ['account_id' => Account::byCode('4010')->id, 'credit' => new Money(640000)],
    ], User::query()->first()->id, cohortId: $cohort->id);

    return app(SettlementService::class)->computeCohortDraft($cohort);
}

it('blocks a coordinator from the finance routes entirely', function () {
    $this->actingAs(financeUser('coordinator'))->get(route('admin.finance'))->assertForbidden();
    $this->actingAs(financeUser('coordinator'))->get(route('admin.finance.settlements'))->assertForbidden();
});

it('returns 403 when a coordinator calls confirmSettlement() directly — the method, not the UI', function () {
    $settlement = draftSettlement();

    Livewire::actingAs(financeUser('coordinator'))
        ->test(SettlementShow::class, ['settlement' => $settlement->id])
        ->assertForbidden();
});

it('returns 403 when an admin (not owner) calls confirmSettlement() directly', function () {
    $settlement = draftSettlement();

    // admin lacks finance.settle — even mount is forbidden
    Livewire::actingAs(financeUser('admin'))
        ->test(SettlementShow::class, ['settlement' => $settlement->id])
        ->assertForbidden();
});

it('lets an owner confirm a settlement through the component', function () {
    $settlement = draftSettlement();

    Livewire::actingAs(financeUser('owner'))
        ->test(SettlementShow::class, ['settlement' => $settlement->id])
        ->call('openConfirm')
        ->call('confirmSettlement');

    expect($settlement->refresh()->status->value)->toBe('posted');
});

it('rejects tampering with the locked settlementId', function () {
    $settlement = draftSettlement();

    expect(fn () => Livewire::actingAs(financeUser('owner'))
        ->test(SettlementShow::class, ['settlement' => $settlement->id])
        ->set('settlementId', 999)
    )->toThrow(Exception::class);
});

it('blocks a viewer from every recording action method', function () {
    Livewire::actingAs(financeUser('viewer'))
        ->test(FinanceHub::class)
        ->assertForbidden();
});

it('blocks recording actions above the caller permission — admin cannot payout', function () {
    Livewire::actingAs(financeUser('admin'))
        ->test(FinanceHub::class)
        ->call('open', 'payout')
        ->assertForbidden();
});

it('records a quick-add expense end to end and attaches it to the ledger', function () {
    $owner = financeUser('owner');

    Livewire::actingAs($owner)
        ->test(QuickAddExpense::class)
        ->call('show')
        ->set('amount', '40.000')
        ->set('category', '6060')
        ->call('save')
        ->assertHasNoErrors();

    $entry = JournalEntry::query()->latest('id')->first();
    $lines = $entry->lines()->get();

    expect($lines)->toHaveCount(2)
        ->and($lines->firstWhere('account_id', Account::byCode('6060')->id)->debit_baisa->baisa)->toBe(40000)
        ->and($lines->firstWhere('account_id', Account::byCode('1020')->id)->credit_baisa->baisa)->toBe(40000);
});

it('quick-add requires a cohort for direct-cost categories', function () {
    Livewire::actingAs(financeUser('owner'))
        ->test(QuickAddExpense::class)
        ->call('show')
        ->set('amount', '25.000')
        ->set('category', '5030')
        ->call('save')
        ->assertHasErrors(['cohort_id']);
});

it('blocks quick-add for roles without finance.record_expense', function () {
    Livewire::actingAs(financeUser('coordinator'))
        ->test(QuickAddExpense::class)
        ->call('show')
        ->assertForbidden();
});

it('records revenue through the hub and the entry lands balanced with cohort linkage', function () {
    $cohort = Cohort::factory()->create();

    Livewire::actingAs(financeUser('admin'))
        ->test(FinanceHub::class)
        ->call('open', 'revenue')
        ->set('amount', '640.000')
        ->set('cohort_id', (string) $cohort->id)
        ->set('description', 'فاتورة وزارة الأوقاف')
        ->call('save')
        ->assertHasNoErrors();

    $entry = JournalEntry::query()->latest('id')->first();

    expect($entry->cohort_id)->toBe($cohort->id)
        ->and($entry->lines()->get()->sum(fn ($l) => $l->debit_baisa->baisa))
        ->toBe($entry->lines()->get()->sum(fn ($l) => $l->credit_baisa->baisa));
});
