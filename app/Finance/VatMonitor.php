<?php

namespace App\Finance;

use App\Models\JournalLine;
use App\Models\Setting;
use Carbon\CarbonInterface;

/**
 * Spec §8.9: rolling 12-month taxable supplies, recomputed nightly and on
 * every revenue posting. Zero-rated supplies count toward the registration
 * threshold; exempt and out-of-scope do not.
 */
class VatMonitor
{
    public const STATE_GREEN = 'green';

    public const STATE_AMBER = 'amber';

    public const STATE_ORANGE = 'orange';

    public const STATE_RED = 'red';

    public function recompute(?CarbonInterface $asOf = null): int
    {
        $asOf ??= now();

        $sums = JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('accounts.code', 'like', '4%')
            ->whereIn('journal_lines.vat_treatment', ['standard', 'zero_rated'])
            ->whereBetween('journal_entries.entry_date', [
                $asOf->copy()->subMonths(12)->addDay()->toDateString(),
                $asOf->toDateString(),
            ])
            ->selectRaw('COALESCE(SUM(journal_lines.credit_baisa - journal_lines.debit_baisa), 0) as taxable')
            ->toBase()
            ->first();

        $taxable = (int) $sums->taxable;

        Setting::put('vat_rolling_taxable_baisa', $taxable);
        Setting::put('vat_rolling_computed_at', $asOf->toIso8601String());

        return $taxable;
    }

    public function current(): int
    {
        $stored = Setting::get('vat_rolling_taxable_baisa');

        return $stored !== null ? (int) $stored : $this->recompute();
    }

    /**
     * @return array{taxable: Money, mandatory: Money, voluntary: Money, state: string, percent_of_mandatory: int}
     */
    public function status(): array
    {
        $taxable = $this->current();
        $mandatory = (int) Setting::get('vat_mandatory_threshold_baisa', 38_500_000);
        $voluntary = (int) Setting::get('vat_voluntary_threshold_baisa', 19_250_000);

        // العتبات الوسيطة للتنبيه (§8.9): 30,000 ر.ع. حد البرتقالي
        $orange = 30_000_000;

        $state = match (true) {
            $taxable >= $mandatory => self::STATE_RED,
            $taxable >= $orange => self::STATE_ORANGE,
            $taxable >= $voluntary => self::STATE_AMBER,
            default => self::STATE_GREEN,
        };

        return [
            'taxable' => new Money($taxable),
            'mandatory' => new Money($mandatory),
            'voluntary' => new Money($voluntary),
            'state' => $state,
            'percent_of_mandatory' => $mandatory > 0 ? intdiv($taxable * 100, $mandatory) : 0,
        ];
    }
}
