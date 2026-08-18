<?php

namespace App\Livewire\Admin\Finance;

use App\Enums\CohortStatus;
use App\Finance\SettlementService;
use App\Models\Cohort;
use App\Models\Settlement;
use DomainException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class SettlementsIndex extends Component
{
    use WithPagination;

    public string $monthlyPeriod = '';

    public function mount(): void
    {
        $this->authorize('finance.settle');
        $this->monthlyPeriod = now()->timezone(config('app.display_timezone'))->subMonth()->format('Y-m');
    }

    public function computeMonthly(): void
    {
        $this->authorize('finance.settle');

        $this->validate(['monthlyPeriod' => ['required', 'date_format:Y-m']]);

        [$year, $month] = array_map('intval', explode('-', $this->monthlyPeriod));

        try {
            $settlement = app(SettlementService::class)->computeMonthlyDraft($year, $month);
        } catch (DomainException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->redirectRoute('admin.finance.settlements.show', ['settlement' => $settlement->id], navigate: true);
    }

    public function computeDraft(int $cohortId): void
    {
        $this->authorize('finance.settle');

        $cohort = Cohort::query()->findOrFail($cohortId);

        try {
            $settlement = app(SettlementService::class)->computeCohortDraft($cohort);
        } catch (DomainException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->redirectRoute('admin.finance.settlements.show', ['settlement' => $settlement->id], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.finance.settlements-index', [
            'readyCohorts' => Cohort::query()
                ->where('status', CohortStatus::Delivered)
                ->whereDoesntHave('settlements', fn ($query) => $query->whereIn('status', ['draft', 'posted']))
                ->with(['course:id,title_ar', 'client:id,name_ar'])
                ->orderByDesc('ends_at')
                ->get(),
            'settlements' => Settlement::query()
                ->with(['cohort.course:id,title_ar', 'cohort:id,code,course_id,title_override_ar'])
                ->orderByDesc('id')
                ->simplePaginate(12),
        ])->title(__('finance.settlements'));
    }
}
