<?php

namespace App\Finance\Reports;

use App\Finance\Money;
use App\Finance\VatMonitor;
use App\Models\Setting;

/**
 * شاشة تقدير الالتزامات الضريبية (spec §8.9). كل عتبة من الإعدادات، لا
 * ثوابت. The mandatory disclaimer lives in the view and cannot be removed.
 */
class TaxEstimate
{
    public function __construct(
        protected VatMonitor $vatMonitor,
        protected IncomeStatement $incomeStatement,
    ) {}

    /**
     * @return array{
     *     year: int,
     *     vat: array{taxable: Money, mandatory: Money, voluntary: Money, state: string, percent_of_mandatory: int},
     *     vat_remaining_to_mandatory: Money,
     *     net_profit_ytd: Money,
     *     cit_rate_percent: int,
     *     cit_reduced_applied: bool,
     *     cit_income_limit: Money,
     *     cit_estimate: Money,
     *     pit_threshold: Money,
     *     pit_rate_percent: int,
     *     pit_effective_date: string,
     * }
     */
    public function build(): array
    {
        $year = (int) now()->timezone(config('app.display_timezone'))->year;
        $vat = $this->vatMonitor->status();

        $income = $this->incomeStatement->build(
            now()->startOfYear(),
            now(),
            'accrual',
        );

        $netProfit = $income['net'];

        $incomeLimit = (int) Setting::get('cit_reduced_income_limit_baisa', 150_000_000);
        $reducedRate = (int) Setting::get('cit_reduced_rate_percent', 3);
        $standardRate = (int) Setting::get('cit_standard_rate_percent', 15);

        // التبسيط المعلن: الأهلية للنسبة المخفضة تُقدَّر بحد الدخل فقط —
        // شرطا رأس المال وعدد الموظفين على المالكَين تأكيدهما مع مستشار
        $reducedApplies = $netProfit->baisa <= $incomeLimit;
        $rate = $reducedApplies ? $reducedRate : $standardRate;

        $estimate = $netProfit->isPositive()
            ? $netProfit->multiplyByPercent($rate)
            : Money::zero();

        return [
            'year' => $year,
            'vat' => $vat,
            'vat_remaining_to_mandatory' => new Money(max(0, $vat['mandatory']->baisa - $vat['taxable']->baisa)),
            'net_profit_ytd' => $netProfit,
            'cit_rate_percent' => $rate,
            'cit_reduced_applied' => $reducedApplies,
            'cit_income_limit' => new Money($incomeLimit),
            'cit_estimate' => $estimate,
            'pit_threshold' => new Money((int) Setting::get('pit_threshold_baisa', 42_000_000)),
            'pit_rate_percent' => (int) Setting::get('pit_rate_percent', 5),
            'pit_effective_date' => (string) Setting::get('pit_effective_date', '2028-01-01'),
        ];
    }
}
