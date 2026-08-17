<?php

namespace App\Finance;

use App\Models\Account;
use App\Models\Cohort;
use App\Models\JournalEntry;
use App\Models\Partner;
use Carbon\CarbonInterface;
use DomainException;

/**
 * The five simple recording actions of spec §8.4 (plus capital
 * contributions). The owners record business events; this class turns them
 * into balanced journal entries. Debit/credit never reaches the UI.
 */
class LedgerRecorder
{
    public function __construct(
        protected JournalPoster $poster,
    ) {}

    /**
     * §8.4-A: إيراد لدفعة — فاتورة صدرت أو استحق الدخل.
     */
    public function recordRevenue(Cohort $cohort, Money $amount, CarbonInterface $date, string $descriptionAr, int $createdBy, string $revenueCode = '4010'): JournalEntry
    {
        $this->assertPositive($amount);

        return $this->poster->post($date, $descriptionAr, [
            ['account_id' => Account::byCode('1100')->id, 'debit' => $amount],
            ['account_id' => Account::byCode($revenueCode)->id, 'credit' => $amount],
        ], $createdBy, cohortId: $cohort->id, invoicingEntityId: $cohort->invoicing_entity_id);
    }

    /**
     * §8.4-B: تحصيل دفعة من الذمم المدينة.
     */
    public function recordPayment(?Cohort $cohort, Money $amount, string $intoAccountCode, CarbonInterface $date, string $descriptionAr, int $createdBy): JournalEntry
    {
        $this->assertPositive($amount);
        $this->assertCashAccount($intoAccountCode);

        return $this->poster->post($date, $descriptionAr, [
            ['account_id' => Account::byCode($intoAccountCode)->id, 'debit' => $amount],
            ['account_id' => Account::byCode('1100')->id, 'credit' => $amount],
        ], $createdBy, cohortId: $cohort?->id, invoicingEntityId: $cohort?->invoicing_entity_id);
    }

    /**
     * §8.4-C: تكلفة مباشرة لدفعة (إعلانات، قاعات، مواد…).
     */
    public function recordDirectCost(Cohort $cohort, string $costCode, Money $amount, string $paidFromCode, CarbonInterface $date, string $descriptionAr, int $createdBy): JournalEntry
    {
        $this->assertPositive($amount);
        $this->assertCashAccount($paidFromCode);

        $costAccount = Account::byCode($costCode);

        if (! str_starts_with($costAccount->code, '5') || $costAccount->code === '5010') {
            throw new DomainException('Direct costs use accounts 5020–5090 — 5010 belongs to the settlement engine.');
        }

        return $this->poster->post($date, $descriptionAr, [
            ['account_id' => $costAccount->id, 'debit' => $amount],
            ['account_id' => Account::byCode($paidFromCode)->id, 'credit' => $amount],
        ], $createdBy, cohortId: $cohort->id);
    }

    /**
     * §8.4-D: مصروف تشغيلي على مستوى المركز (لا يخص دفعة).
     */
    public function recordOperatingExpense(string $expenseCode, Money $amount, string $paidFromCode, CarbonInterface $date, string $descriptionAr, int $createdBy): JournalEntry
    {
        $this->assertPositive($amount);
        $this->assertCashAccount($paidFromCode);

        $expenseAccount = Account::byCode($expenseCode);

        if (! str_starts_with($expenseAccount->code, '6')) {
            throw new DomainException('Operating expenses use 6xxx accounts.');
        }

        return $this->poster->post($date, $descriptionAr, [
            ['account_id' => $expenseAccount->id, 'debit' => $amount],
            ['account_id' => Account::byCode($paidFromCode)->id, 'credit' => $amount],
        ], $createdBy);
    }

    /**
     * §8.4-F: مسحوبات شريك — من الحساب الجاري إلى جيبه.
     */
    public function recordPartnerPayout(Partner $partner, Money $amount, string $paidFromCode, CarbonInterface $date, string $descriptionAr, int $createdBy): JournalEntry
    {
        $this->assertPositive($amount);
        $this->assertCashAccount($paidFromCode);

        return $this->poster->post($date, $descriptionAr, [
            ['account_id' => $this->partnerCurrentAccount($partner)->id, 'debit' => $amount, 'partner_id' => $partner->id],
            ['account_id' => Account::byCode($paidFromCode)->id, 'credit' => $amount],
        ], $createdBy);
    }

    /**
     * §8.4-G: مساهمة رأس مال من شريك.
     */
    public function recordCapitalContribution(Partner $partner, Money $amount, string $intoAccountCode, CarbonInterface $date, string $descriptionAr, int $createdBy): JournalEntry
    {
        $this->assertPositive($amount);
        $this->assertCashAccount($intoAccountCode);

        $capitalAccount = Account::query()
            ->where('partner_id', $partner->id)
            ->where('code', 'like', '301%')
            ->firstOrFail();

        return $this->poster->post($date, $descriptionAr, [
            ['account_id' => Account::byCode($intoAccountCode)->id, 'debit' => $amount],
            ['account_id' => $capitalAccount->id, 'credit' => $amount, 'partner_id' => $partner->id],
        ], $createdBy);
    }

    public function partnerCurrentAccount(Partner $partner): Account
    {
        return Account::query()
            ->where('partner_id', $partner->id)
            ->where('code', 'like', '302%')
            ->firstOrFail();
    }

    protected function assertPositive(Money $amount): void
    {
        if (! $amount->isPositive()) {
            throw new DomainException('Recorded amounts must be positive — corrections go through reversals.');
        }
    }

    protected function assertCashAccount(string $code): void
    {
        if (! in_array($code, ['1010', '1020', '1030'], true)) {
            throw new DomainException('Cash movements use 1010/1020/1030 only.');
        }
    }
}
