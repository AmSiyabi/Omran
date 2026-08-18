<?php

namespace App\Livewire\Admin\Reports;

use App\Finance\Reports\TaxEstimate;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class TaxScreen extends Component
{
    public function mount(): void
    {
        $this->authorize('reports.tax');
    }

    public function render(): View
    {
        return view('livewire.admin.reports.tax-screen', [
            'estimate' => app(TaxEstimate::class)->build(),
        ])->title(__('finance.tax_estimate_title'));
    }
}
