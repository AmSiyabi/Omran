<?php

namespace App\Livewire\Admin\Reports;

use App\Finance\Reports\AnnualPartnerIncome;
use App\Finance\Reports\CashFlow;
use App\Finance\Reports\CohortProfitability;
use App\Finance\Reports\IncomeStatement;
use App\Finance\Reports\PartnerStatement;
use App\Finance\Reports\PdfRenderer;
use App\Finance\Reports\ReceivablesAging;
use App\Finance\Reports\XlsxExport;
use App\Finance\VatMonitor;
use App\Models\Partner;
use App\Support\Baisa;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.admin')]
class ReportsHub extends Component
{
    public const REPORTS = ['income', 'cashflow', 'cohorts', 'partner', 'aging', 'annual', 'vat'];

    #[Url]
    public string $report = 'income';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $basis = 'accrual';

    #[Url]
    public string $partnerId = '';

    #[Url]
    public string $year = '';

    public function mount(): void
    {
        $this->authorize('reports.view');

        $tz = config('app.display_timezone');
        $this->from = $this->from !== '' ? $this->from : now()->timezone($tz)->startOfYear()->toDateString();
        $this->to = $this->to !== '' ? $this->to : now()->timezone($tz)->toDateString();
        $this->year = $this->year !== '' ? $this->year : (string) now()->timezone($tz)->year;

        if (! in_array($this->report, self::REPORTS, true)) {
            $this->report = 'income';
        }
    }

    public function selectReport(string $report): void
    {
        $this->authorize('reports.view');

        if (in_array($report, self::REPORTS, true)) {
            $this->report = $report;
        }
    }

    public function exportXlsx(): StreamedResponse
    {
        $this->authorize('reports.export');

        return XlsxExport::stream("omran-{$this->report}-{$this->to}.xlsx", $this->exportRows());
    }

    public function exportPdf(): StreamedResponse
    {
        $this->authorize('reports.export');

        $html = view('livewire.admin.reports.pdf.'.$this->report, $this->reportData())->render();
        $pdf = app(PdfRenderer::class)->render($html, $this->reportTitle());
        $filename = "omran-{$this->report}-{$this->to}.pdf";

        return response()->streamDownload(fn () => print ($pdf), $filename, ['Content-Type' => 'application/pdf']);
    }

    public function render(): View
    {
        return view('livewire.admin.reports.reports-hub', $this->reportData() + [
            'reportTitle' => $this->reportTitle(),
            'partners' => Partner::query()->where('is_active', true)->orderBy('id')->get(['id', 'display_name_ar']),
        ])->title($this->reportTitle());
    }

    /**
     * @return array<string, mixed>
     */
    protected function reportData(): array
    {
        $from = Carbon::parse($this->from);
        $to = Carbon::parse($this->to);

        return match ($this->report) {
            'income' => ['income' => app(IncomeStatement::class)->build($from, $to, $this->basis === 'cash' ? 'cash' : 'accrual')],
            'cashflow' => ['cashflow' => app(CashFlow::class)->build($from, $to)],
            'cohorts' => ['cohorts' => app(CohortProfitability::class)->list()],
            'partner' => ['statement' => $this->partnerId !== ''
                ? app(PartnerStatement::class)->build(Partner::query()->findOrFail((int) $this->partnerId), $from, $to)
                : null],
            'aging' => ['aging' => app(ReceivablesAging::class)->build($to)],
            'annual' => ['annual' => app(AnnualPartnerIncome::class)->build((int) $this->year)],
            'vat' => ['vat' => app(VatMonitor::class)->status()],
            default => [],
        };
    }

    /**
     * @return list<array<string, string>>
     */
    protected function exportRows(): array
    {
        $data = $this->reportData();

        return match ($this->report) {
            'income' => $this->incomeRows($data['income']),
            'cashflow' => $this->cashflowRows($data['cashflow']),
            'cohorts' => $data['cohorts']->map(fn ($row) => [
                'الدفعة' => ($row->title_override_ar ?? $row->course_title).' — '.$row->cohort_code,
                'الإيرادات' => Baisa::toString((int) $row->gross_revenue_baisa),
                'التكاليف المباشرة' => Baisa::toString((int) $row->direct_costs_baisa),
                'حصة المنفذين' => Baisa::toString((int) $row->deliverer_share_baisa),
                'حصة المركز' => Baisa::toString((int) $row->center_share_baisa),
                'الهامش %' => number_format($row->margin_basis_points / 100, 2),
            ])->values()->all(),
            'partner' => $data['statement'] === null ? [] : array_map(fn ($row) => [
                'التاريخ' => $row['date'],
                'القيد' => $row['entry_number'],
                'البيان' => $row['description'],
                'له (دائن)' => $row['credit']->toDecimalString(),
                'عليه (مدين)' => $row['debit']->toDecimalString(),
                'الرصيد' => $row['balance']->toDecimalString(),
            ], $data['statement']['rows']),
            'aging' => array_map(fn ($row) => [
                'الدفعة' => $row['label'],
                __('finance.aging_0_30') => $row['buckets']['0_30']->toDecimalString(),
                __('finance.aging_31_60') => $row['buckets']['31_60']->toDecimalString(),
                __('finance.aging_61_90') => $row['buckets']['61_90']->toDecimalString(),
                __('finance.aging_90_plus') => $row['buckets']['90_plus']->toDecimalString(),
                'الإجمالي' => $row['total']->toDecimalString(),
            ], $data['aging']['rows']),
            'annual' => array_map(fn ($row) => [
                'الشريك' => $row['partner']->display_name_ar,
                'إجمالي الاستحقاقات' => $row['allocated']->toDecimalString(),
                'إجمالي المسحوبات' => $row['paid_out']->toDecimalString(),
                'صافي الحركة' => $row['net_movement']->toDecimalString(),
            ], $data['annual']['rows']),
            'vat' => [[
                'الإيرادات الخاضعة (12 شهراً)' => $data['vat']['taxable']->toDecimalString(),
                'حد التسجيل الإلزامي' => $data['vat']['mandatory']->toDecimalString(),
                'حد التسجيل الاختياري' => $data['vat']['voluntary']->toDecimalString(),
                'الحالة' => __('finance.vat_state_'.$data['vat']['state']),
            ]],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $income
     * @return list<array<string, string>>
     */
    protected function incomeRows(array $income): array
    {
        $rows = [];

        foreach (['revenue' => __('finance.revenue'), 'direct_costs' => __('finance.direct_costs'), 'opex' => __('finance.operating_expenses')] as $section => $label) {
            foreach ($income[$section] as $line) {
                $rows[] = ['القسم' => $label, 'الحساب' => $line['code'].' — '.$line['name_ar'], 'المبلغ' => $line['amount']->toDecimalString()];
            }

            $rows[] = ['القسم' => $label, 'الحساب' => 'الإجمالي', 'المبلغ' => $income[$section.'_total']->toDecimalString()];
        }

        $rows[] = ['القسم' => __('finance.net_profit'), 'الحساب' => '', 'المبلغ' => $income['net']->toDecimalString()];

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $cashflow
     * @return list<array<string, string>>
     */
    protected function cashflowRows(array $cashflow): array
    {
        $rows = [['البند' => __('finance.opening_balance'), 'المبلغ' => $cashflow['opening']->toDecimalString()]];

        foreach ($cashflow['inflows'] as $row) {
            $rows[] = ['البند' => $row['label'], 'المبلغ' => $row['amount']->toDecimalString()];
        }

        $rows[] = ['البند' => __('finance.cash_in').' — الإجمالي', 'المبلغ' => $cashflow['inflows_total']->toDecimalString()];

        foreach ($cashflow['outflows'] as $row) {
            $rows[] = ['البند' => $row['label'], 'المبلغ' => '-'.$row['amount']->toDecimalString()];
        }

        $rows[] = ['البند' => __('finance.cash_out').' — الإجمالي', 'المبلغ' => '-'.$cashflow['outflows_total']->toDecimalString()];
        $rows[] = ['البند' => __('finance.net_change'), 'المبلغ' => $cashflow['net_change']->toDecimalString()];
        $rows[] = ['البند' => __('finance.closing_balance'), 'المبلغ' => $cashflow['closing']->toDecimalString()];

        return $rows;
    }

    protected function reportTitle(): string
    {
        return __('finance.report_'.$this->report);
    }
}
