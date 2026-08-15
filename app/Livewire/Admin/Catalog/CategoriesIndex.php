<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Category;
use App\Support\ArabicSlug;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class CategoriesIndex extends Component
{
    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public bool $showForm = false;

    public string $name_ar = '';

    public string $name_en = '';

    public string $description_ar = '';

    public string $accent_color = '#cda34f';

    public int $sort_order = 0;

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
    }

    public function create(): void
    {
        $this->authorize('create', Category::class);

        $this->reset('editingId', 'name_ar', 'name_en', 'description_ar');
        $this->accent_color = '#cda34f';
        $this->sort_order = (int) Category::query()->max('sort_order') + 1;
        $this->is_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $category = Category::query()->findOrFail($id);
        $this->authorize('update', $category);

        $this->editingId = $category->id;
        $this->name_ar = $category->name_ar;
        $this->name_en = (string) $category->name_en;
        $this->description_ar = (string) $category->description_ar;
        $this->accent_color = $category->accent_color ?? '#cda34f';
        $this->sort_order = $category->sort_order;
        $this->is_active = $category->is_active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $validated['name_en'] = $validated['name_en'] ?: null;
        $validated['description_ar'] = $validated['description_ar'] ?: null;

        if ($this->editingId !== null) {
            $category = Category::query()->findOrFail($this->editingId);
            $this->authorize('update', $category);
            $category->update($validated);
        } else {
            $this->authorize('create', Category::class);
            $validated['slug'] = ArabicSlug::generate($validated['name_ar'], 'categories');
            Category::query()->create($validated);
        }

        $this->showForm = false;
        $this->dispatch('toast', type: 'success', message: __('courses.category_saved'));
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function toggleActive(int $id): void
    {
        $category = Category::query()->findOrFail($id);
        $this->authorize('update', $category);

        $category->update(['is_active' => ! $category->is_active]);
    }

    public function confirmDelete(int $id): void
    {
        $category = Category::query()->findOrFail($id);
        $this->authorize('delete', $category);

        $this->deletingId = $category->id;
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

        $category = Category::query()->findOrFail($this->deletingId);
        $this->authorize('delete', $category);

        if ($category->courses()->withTrashed()->exists()) {
            $this->deletingId = null;
            $this->dispatch('toast', type: 'error', message: __('courses.category_has_courses'));

            return;
        }

        $category->delete();
        $this->deletingId = null;
        $this->dispatch('toast', type: 'success', message: __('courses.category_deleted'));
    }

    public function render(): View
    {
        return view('livewire.admin.catalog.categories-index', [
            'categories' => Category::query()
                ->withCount('courses')
                ->orderBy('sort_order')
                ->orderBy('name_ar')
                ->get(),
            'deletingCategory' => $this->deletingId !== null
                ? Category::query()->find($this->deletingId)
                : null,
        ])->title(__('courses.categories'));
    }
}
