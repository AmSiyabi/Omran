<?php

namespace App\Livewire\Admin\Catalog;

use App\Enums\CohortStatus;
use App\Models\Cohort;
use App\Support\Baisa;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class CohortsIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Cohort::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $cohorts = Cohort::query()
            ->select(['id', 'course_id', 'code', 'title_override_ar', 'status', 'starts_at', 'capacity', 'seats_taken', 'price_baisa', 'is_free', 'client_id'])
            ->with(['course:id,title_ar', 'client:id,name_ar'])
            ->when($this->search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('code', 'like', "%{$this->search}%")
                ->orWhereHas('course', fn ($c) => $c->where('title_ar', 'like', "%{$this->search}%"))))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('starts_at')
            ->simplePaginate(12);

        return view('livewire.admin.catalog.cohorts-index', [
            'cohorts' => $cohorts,
            'statuses' => CohortStatus::cases(),
            'baisa' => Baisa::class,
        ])->title(__('courses.cohorts'));
    }
}
