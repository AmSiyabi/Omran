<?php

namespace App\Livewire\Admin\Catalog;

use App\Enums\CourseLevel;
use App\Models\Category;
use App\Models\Course;
use App\Support\ArabicSlug;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class CourseForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $courseId = null;

    public string $title_ar = '';

    public string $title_en = '';

    public string $summary_ar = '';

    public string $description_ar = '';

    /** @var array<int, string> */
    public array $outcomes_ar = [''];

    public string $target_audience_ar = '';

    public string $prerequisites_ar = '';

    public string $duration_hours = '12';

    public string $level = 'all';

    public string $category_id = '';

    public string $meta_title_ar = '';

    public string $meta_description_ar = '';

    /** @var TemporaryUploadedFile|null */
    public $cover = null;

    public function mount(?int $course = null): void
    {
        if ($course !== null) {
            $model = Course::query()->findOrFail($course);
            $this->authorize('update', $model);

            $this->courseId = $model->id;
            $this->title_ar = $model->title_ar;
            $this->title_en = (string) $model->title_en;
            $this->summary_ar = $model->summary_ar;
            $this->description_ar = $model->description_ar;
            $this->outcomes_ar = $model->outcomes_ar !== [] ? $model->outcomes_ar : [''];
            $this->target_audience_ar = (string) $model->target_audience_ar;
            $this->prerequisites_ar = (string) $model->prerequisites_ar;
            $this->duration_hours = rtrim(rtrim((string) $model->duration_hours, '0'), '.');
            $this->level = $model->level->value;
            $this->category_id = (string) $model->category_id;
            $this->meta_title_ar = (string) $model->meta_title_ar;
            $this->meta_description_ar = (string) $model->meta_description_ar;
        } else {
            $this->authorize('create', Course::class);
        }
    }

    public function addOutcome(): void
    {
        $this->authorizeAction();
        $this->outcomes_ar[] = '';
    }

    public function removeOutcome(int $index): void
    {
        $this->authorizeAction();

        unset($this->outcomes_ar[$index]);
        $this->outcomes_ar = array_values($this->outcomes_ar);

        if ($this->outcomes_ar === []) {
            $this->outcomes_ar = [''];
        }
    }

    public function save(): void
    {
        $this->authorizeAction();

        $validated = $this->validate([
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'summary_ar' => ['required', 'string', 'max:500'],
            'description_ar' => ['required', 'string', 'max:20000'],
            'outcomes_ar' => ['required', 'array', 'min:1'],
            'outcomes_ar.*' => ['required', 'string', 'max:500'],
            'target_audience_ar' => ['nullable', 'string', 'max:2000'],
            'prerequisites_ar' => ['nullable', 'string', 'max:2000'],
            'duration_hours' => ['required', 'numeric', 'min:0.5', 'max:1000'],
            'level' => ['required', Rule::enum(CourseLevel::class)],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'meta_title_ar' => ['nullable', 'string', 'max:255'],
            'meta_description_ar' => ['nullable', 'string', 'max:500'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,webp,avif', 'max:5120'],
        ]);

        unset($validated['cover']);

        $validated['title_en'] = $validated['title_en'] ?: null;
        $validated['target_audience_ar'] = $validated['target_audience_ar'] ?: null;
        $validated['prerequisites_ar'] = $validated['prerequisites_ar'] ?: null;
        $validated['meta_title_ar'] = $validated['meta_title_ar'] ?: null;
        $validated['meta_description_ar'] = $validated['meta_description_ar'] ?: null;
        $validated['outcomes_ar'] = array_values(array_filter($validated['outcomes_ar'], fn (string $o) => trim($o) !== ''));

        if ($this->courseId !== null) {
            $course = Course::query()->findOrFail($this->courseId);
            $course->update($validated);
        } else {
            $validated['slug'] = ArabicSlug::generate($validated['title_ar'], 'courses');
            $course = Course::query()->create($validated);
        }

        if ($this->cover !== null) {
            $course->addMedia($this->cover->getRealPath())
                ->usingFileName($course->slug.'.'.$this->cover->getClientOriginalExtension())
                ->toMediaCollection('cover');
        }

        $this->dispatch('toast', type: 'success', message: __('courses.course_saved'));
        $this->redirectRoute('admin.courses', navigate: true);
    }

    public function render(): View
    {
        $existingCover = null;

        if ($this->courseId !== null) {
            $course = Course::query()->with('media')->findOrFail($this->courseId);
            $existingCover = $course->getFirstMediaUrl('cover', 'thumb') ?: null;
        }

        return view('livewire.admin.catalog.course-form', [
            'categories' => Category::query()->orderBy('sort_order')->get(['id', 'name_ar']),
            'levels' => CourseLevel::cases(),
            'existingCover' => $existingCover,
        ])->title($this->courseId ? __('courses.edit_course') : __('courses.new_course'));
    }

    protected function authorizeAction(): void
    {
        if ($this->courseId !== null) {
            $this->authorize('update', Course::query()->findOrFail($this->courseId));
        } else {
            $this->authorize('create', Course::class);
        }
    }
}
