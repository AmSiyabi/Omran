<?php

use App\Finance\JournalPoster;
use App\Finance\Money;
use App\Finance\Reports\CashFlow;
use App\Finance\Reports\CohortProfitability;
use App\Finance\Reports\IncomeStatement;
use App\Finance\Reports\PartnerStatement;
use App\Finance\Reports\ReceivablesAging;
use App\Finance\VatMonitor;
use App\Jobs\RefreshCohortFinancialSummary;
use App\Models\Account;
use App\Models\Cohort;
use App\Models\JournalLine;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->hamad = User::factory()->create()->partner()->create([
        'display_name_ar' => 'حمد', 'bio_ar' => 'شريك', 'ownership_percent' => 50,
        'effective_from' => now()->toDateString(),
    ]);

    $this->seed(ChartOfAccountsSeeder::class);
    $this->user = User::factory()->create();
    $this->poster = app(JournalPoster::class);
});

function postPair(string $debitCode, string $creditCode, int $amount, ?string $date = null, ?int $cohortId = null): void
{
    test()->poster->post(
        $date !== null ? Carbon::parse($date) : now(),
        "قيد {$debitCode}/{$creditCode}",
        [
            ['account_id' => Account::byCode($debitCode)->id, 'debit' => new Money($amount)],
            ['account_id' => Account::byCode($creditCode)->id, 'credit' => new Money($amount)],
        ],
        test()->user->id,
        cohortId: $cohortId,
    );
}

it('income statement ties exactly to the sum of journal lines', function () {
    postPair('1100', '4010', 1000000);           // إيراد دورات
    postPair('1100', '4020', 250000);            // إيراد استشارات
    postPair('5030', '1020', 150000);            // إعلانات
    postPair('6010', '1020', 42500);             // اشتراكات

    $report = app(IncomeStatement::class)->build(now()->startOfYear(), now());

    $rawRevenue = (int) JournalLine::query()
        ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
        ->where('accounts.code', 'like', '4%')
        ->selectRaw('COALESCE(SUM(journal_lines.credit_baisa - journal_lines.debit_baisa), 0) as total')
        ->toBase()->value('total');

    expect($report['revenue_total']->baisa)->toBe($rawRevenue)
        ->and($report['revenue_total']->baisa)->toBe(1250000)
        ->and($report['direct_costs_total']->baisa)->toBe(150000)
        ->and($report['opex_total']->baisa)->toBe(42500)
        ->and($report['net']->baisa)->toBe(1250000 - 150000 - 42500);
});

it('cash-basis income statement counts only collected money', function () {
    postPair('1100', '4010', 1000000);           // فاتورة — استحقاق فقط
    postPair('1020', '1100', 400000);            // تحصيل جزئي

    $accrual = app(IncomeStatement::class)->build(now()->startOfYear(), now(), 'accrual');
    $cash = app(IncomeStatement::class)->build(now()->startOfYear(), now(), 'cash');

    expect($accrual['revenue_total']->baisa)->toBe(1000000)
        ->and($cash['revenue_total']->baisa)->toBe(400000);
});

it('cash flow closing balance equals the sum of 1010+1020+1030 balances', function () {
    postPair('1020', '3010', 5000000, now()->subMonths(2)->toDateString());  // مساهمة قبل الفترة
    postPair('1020', '1100', 600000);            // تحصيل داخل الفترة
    postPair('5030', '1020', 150000);            // مصروف مدفوع
    postPair('6010', '1020', 42500);

    $report = app(CashFlow::class)->build(now()->startOfMonth(), now());

    $rawCash = (int) JournalLine::query()
        ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
        ->whereIn('accounts.code', ['1010', '1020', '1030'])
        ->selectRaw('COALESCE(SUM(journal_lines.debit_baisa - journal_lines.credit_baisa), 0) as total')
        ->toBase()->value('total');

    expect($report['closing']->baisa)->toBe($rawCash)
        ->and($report['opening']->baisa)->toBe(5000000)
        ->and($report['net_change']->baisa)->toBe(600000 - 150000 - 42500);
});

it('partner statement closing balance equals the current-account journal balance', function () {
    $current = Account::query()->where('partner_id', $this->hamad->id)->where('code', 'like', '302%')->first();

    // استحقاق ثم مسحوبات
    $this->poster->post(now()->subDays(10), 'استحقاق', [
        ['account_id' => Account::byCode('3090')->id, 'debit' => new Money(500000)],
        ['account_id' => $current->id, 'credit' => new Money(500000), 'partner_id' => $this->hamad->id],
    ], $this->user->id);

    $this->poster->post(now()->subDays(3), 'مسحوبات', [
        ['account_id' => $current->id, 'debit' => new Money(200000), 'partner_id' => $this->hamad->id],
        ['account_id' => Account::byCode('1020')->id, 'credit' => new Money(200000)],
    ], $this->user->id);

    $statement = app(PartnerStatement::class)->build($this->hamad, now()->startOfYear(), now());

    expect($statement['closing']->baisa)->toBe(300000)
        ->and($statement['closing']->baisa)->toBe(app(PartnerStatement::class)->currentBalance($this->hamad)->baisa)
        ->and($statement['rows'])->toHaveCount(2)
        ->and(end($statement['rows'])['balance']->baisa)->toBe(300000);
});

it('ages receivables into the right buckets with FIFO payments', function () {
    $cohort = Cohort::factory()->create();

    postPair('1100', '4010', 500000, now()->subDays(100)->toDateString(), $cohort->id); // فاتورة قديمة
    postPair('1100', '4010', 300000, now()->subDays(45)->toDateString(), $cohort->id);  // فاتورة أوسط
    postPair('1020', '1100', 500000, now()->subDays(40)->toDateString(), $cohort->id);  // تحصيل يغطي الأقدم

    $aging = app(ReceivablesAging::class)->build(now());

    expect($aging['grand_total']->baisa)->toBe(300000)
        ->and($aging['totals']['31_60']->baisa)->toBe(300000)
        ->and($aging['totals']['90_plus']->baisa)->toBe(0);
});

it('computes the VAT rolling 12-month window correctly across a year boundary', function () {
    // 13 شهراً مضت: خارج النافذة — 11 شهراً: داخلها (تعبر رأس السنة)
    postPair('1100', '4010', 9000000, now()->subMonths(13)->toDateString());
    postPair('1100', '4010', 7000000, now()->subMonths(11)->toDateString());
    postPair('1100', '4010', 5000000, now()->subDays(5)->toDateString());

    $taxable = app(VatMonitor::class)->recompute();

    expect($taxable)->toBe(12000000);
});

it('excludes exempt revenue from the VAT taxable total', function () {
    $this->poster->post(now(), 'إيراد معفى', [
        ['account_id' => Account::byCode('1100')->id, 'debit' => new Money(2000000)],
        ['account_id' => Account::byCode('4010')->id, 'credit' => new Money(2000000), 'vat_treatment' => 'exempt'],
    ], $this->user->id);

    postPair('1100', '4010', 1000000);

    expect(app(VatMonitor::class)->recompute())->toBe(1000000);
});

it('materializes cohort summaries that tie to the journal (§11.2)', function () {
    $cohort = Cohort::factory()->create();

    postPair('1100', '4010', 1000000, cohortId: $cohort->id);   // فاتورة
    postPair('1020', '1100', 600000, cohortId: $cohort->id);    // تحصيل جزئي
    postPair('5030', '1020', 150000, cohortId: $cohort->id);    // إعلانات

    // المهمة المُصفّاة تعمل afterCommit في الإنتاج — هنا تُنفذ يدوياً
    (new RefreshCohortFinancialSummary($cohort->id))->handle();

    $row = app(CohortProfitability::class)->list()->firstWhere('cohort_id', $cohort->id);

    expect($row)->not->toBeNull()
        ->and((int) $row->gross_revenue_baisa)->toBe(1000000)
        ->and((int) $row->collected_baisa)->toBe(600000)
        ->and((int) $row->receivable_baisa)->toBe(400000)
        ->and((int) $row->direct_costs_baisa)->toBe(150000)
        ->and((int) $row->net_result_baisa)->toBe(850000);
});

it('uses editable settings for the VAT thresholds — nothing hardcoded', function () {
    postPair('1100', '4010', 5000000);
    app(VatMonitor::class)->recompute();

    Setting::put('vat_mandatory_threshold_baisa', 4000000);

    $status = app(VatMonitor::class)->status();

    expect($status['state'])->toBe(VatMonitor::STATE_RED)
        ->and($status['mandatory']->baisa)->toBe(4000000);
});
