<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Category;
use App\Models\Course;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class CoursesIndex extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $deletingId = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $categoryFilter = '';

    #[Url]
    public string $statusFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Course::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function togglePublish(int $id): void
    {
        $course = Course::query()->findOrFail($id);
        $this->authorize('publish', $course);

        $course->update([
            'is_published' => ! $course->is_published,
            'published_at' => $course->is_published ? $course->published_at : now(),
        ]);

        $this->dispatch('toast', type: 'success', message: $course->is_published
            ? __('courses.course_published')
            : __('courses.course_unpublished'));
    }

    public function confirmDelete(int $id): void
    {
        $course = Course::query()->findOrFail($id);
        $this->authorize('delete', $course);

        $this->deletingId = $course->id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        $course = Course::query()->findOrFail($this->deletingId);
        $this->authorize('delete', $course);

        if ($course->cohorts()->withTrashed()->exists()) {
            $this->deletingId = null;
            $this->dispatch('toast', type: 'error', message: __('courses.course_has_cohorts'));

            return;
        }

        $course->delete();
        $this->deletingId = null;
        $this->dispatch('toast', type: 'success', message: __('courses.course_deleted'));
    }

    public function render(): View
    {
        $courses = Course::query()
            ->select(['id', 'slug', 'category_id', 'title_ar', 'summary_ar', 'duration_hours', 'level', 'is_published'])
            ->with('category:id,name_ar,accent_color')
            ->withCount('cohorts')
            ->when($this->search !== '', fn ($query) => $query->where('title_ar', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter !== '', fn ($query) => $query->where('category_id', (int) $this->categoryFilter))
            ->when($this->statusFilter === 'published', fn ($query) => $query->where('is_published', true))
            ->when($this->statusFilter === 'draft', fn ($query) => $query->where('is_published', false))
            ->latest('id')
            ->simplePaginate(12);

        return view('livewire.admin.catalog.courses-index', [
            'courses' => $courses,
            'categories' => Category::query()->orderBy('sort_order')->get(['id', 'name_ar']),
            'deletingCourse' => $this->deletingId !== null
                ? Course::query()->find($this->deletingId)
                : null,
        ])->title(__('courses.courses'));
    }
}
