<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\CohortDeliverer;
use App\Models\Instructor;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class InstructorsIndex extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public bool $showForm = false;

    #[Url(as: 'q')]
    public string $search = '';

    public string $name_ar = '';

    public string $name_en = '';

    public string $specialization_ar = '';

    public string $email = '';

    public string $phone = '';

    public string $bio_ar = '';

    public bool $is_public = true;

    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Instructor::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Instructor::class);

        $this->reset('editingId', 'name_ar', 'name_en', 'specialization_ar', 'email', 'phone', 'bio_ar', 'notes');
        $this->is_public = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $instructor = Instructor::query()->findOrFail($id);
        $this->authorize('update', $instructor);

        $this->editingId = $instructor->id;
        $this->name_ar = $instructor->name_ar;
        $this->name_en = (string) $instructor->name_en;
        $this->specialization_ar = (string) $instructor->specialization_ar;
        $this->email = (string) $instructor->email;
        $this->phone = (string) $instructor->phone;
        $this->bio_ar = (string) $instructor->bio_ar;
        $this->is_public = $instructor->is_public;
        $this->notes = (string) $instructor->notes;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'specialization_ar' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio_ar' => ['nullable', 'string', 'max:3000'],
            'is_public' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach (['name_en', 'specialization_ar', 'email', 'phone', 'bio_ar', 'notes'] as $optional) {
            $validated[$optional] = $validated[$optional] ?: null;
        }

        if ($this->editingId !== null) {
            $instructor = Instructor::query()->findOrFail($this->editingId);
            $this->authorize('update', $instructor);
            $instructor->update($validated);
        } else {
            $this->authorize('create', Instructor::class);
            Instructor::query()->create($validated);
        }

        $this->showForm = false;
        $this->dispatch('toast', type: 'success', message: __('courses.instructor_saved'));
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function confirmDelete(int $id): void
    {
        $instructor = Instructor::query()->findOrFail($id);
        $this->authorize('delete', $instructor);

        $this->deletingId = $instructor->id;
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

        $instructor = Instructor::query()->findOrFail($this->deletingId);
        $this->authorize('delete', $instructor);

        if (CohortDeliverer::query()->where('instructor_id', $instructor->id)->exists()) {
            $this->deletingId = null;
            $this->dispatch('toast', type: 'error', message: __('courses.instructor_in_use'));

            return;
        }

        $instructor->delete();
        $this->deletingId = null;
        $this->dispatch('toast', type: 'success', message: __('courses.instructor_deleted'));
    }

    public function render(): View
    {
        return view('livewire.admin.catalog.instructors-index', [
            'instructors' => Instructor::query()
                ->when($this->search !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('name_ar', 'like', "%{$this->search}%")
                    ->orWhere('specialization_ar', 'like', "%{$this->search}%")))
                ->orderBy('name_ar')
                ->simplePaginate(15),
            'deletingInstructor' => $this->deletingId !== null
                ? Instructor::query()->find($this->deletingId)
                : null,
        ])->title(__('courses.instructors'));
    }
}
