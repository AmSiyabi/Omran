<?php

namespace App\Finance\Reports;

use App\Finance\Money;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * أعمار الذمم المدينة (spec §8.10): 0–30 / 31–60 / 61–90 / 90+.
 * Ministries pay slowly — this matters. Payments apply FIFO against the
 * oldest invoices per cohort.
 */
class ReceivablesAging
{
    /**
     * @return array{
     *     rows: list<array{cohort_id: ?int, label: string, buckets: array<string, Money>, total: Money}>,
     *     totals: array<string, Money>,
     *     grand_total: Money,
     * }
     */
    public function build(CarbonInterface $asOf): array
    {
        $lines = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->leftJoin('cohorts', 'cohorts.id', '=', 'journal_lines.cohort_id')
            ->leftJoin('courses', 'courses.id', '=', 'cohorts.course_id')
            ->where('journal_entries.status', 'posted')
            ->where('accounts.code', '1100')
            ->where('journal_entries.entry_date', '<=', $asOf->toDateString())
            ->orderBy('journal_entries.entry_date')
            ->select([
                'journal_lines.cohort_id', 'journal_lines.debit_baisa', 'journal_lines.credit_baisa',
                'journal_entries.entry_date', 'cohorts.code as cohort_code',
                'cohorts.title_override_ar', 'courses.title_ar as course_title',
            ])
            ->toBase()
            ->get()
            ->groupBy(fn (object $line): int => (int) ($line->cohort_id ?? 0));

        $bucketKeys = ['0_30', '31_60', '61_90', '90_plus'];
        $rows = [];
        $totals = array_fill_keys($bucketKeys, 0);

        foreach ($lines as $cohortKey => $cohortLines) {
            // فواتير بالترتيب الزمني، والتحصيلات تُطبق على الأقدم أولاً (FIFO)
            $invoices = [];
            $payments = 0;

            foreach ($cohortLines as $line) {
                if ((int) $line->debit_baisa > 0) {
                    $invoices[] = ['date' => (string) $line->entry_date, 'remaining' => (int) $line->debit_baisa];
                }
                $payments += (int) $line->credit_baisa;
            }

            foreach ($invoices as $index => $invoice) {
                if ($payments <= 0) {
                    break;
                }

                $applied = min($payments, $invoice['remaining']);
                $invoices[$index]['remaining'] -= $applied;
                $payments -= $applied;
            }

            $buckets = array_fill_keys($bucketKeys, 0);

            foreach ($invoices as $invoice) {
                if ($invoice['remaining'] <= 0) {
                    continue;
                }

                $age = (int) $asOf->copy()->startOfDay()->diffInDays(
                    Carbon::parse($invoice['date'])->startOfDay(), true,
                );

                $bucket = match (true) {
                    $age <= 30 => '0_30',
                    $age <= 60 => '31_60',
                    $age <= 90 => '61_90',
                    default => '90_plus',
                };

                $buckets[$bucket] += $invoice['remaining'];
                $totals[$bucket] += $invoice['remaining'];
            }

            $rowTotal = array_sum($buckets);

            if ($rowTotal === 0) {
                continue;
            }

            $first = $cohortLines->first();

            $rows[] = [
                'cohort_id' => $cohortKey === 0 ? null : (int) $cohortKey,
                'label' => $cohortKey === 0
                    ? 'ذمم غير مرتبطة بدفعة'
                    : ($first->title_override_ar ?? $first->course_title).' — '.$first->cohort_code,
                'buckets' => array_map(fn (int $amount) => new Money($amount), $buckets),
                'total' => new Money($rowTotal),
            ];
        }

        return [
            'rows' => $rows,
            'totals' => array_map(fn (int $amount) => new Money($amount), $totals),
            'grand_total' => new Money(array_sum($totals)),
        ];
    }
}
