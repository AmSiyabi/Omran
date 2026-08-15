<?php

namespace App\Livewire\Admin\Catalog;

use App\Enums\DeliveryMode;
use App\Models\Client;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\InvoicingEntity;
use App\Support\Baisa;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class CohortForm extends Component
{
    #[Locked]
    public ?int $cohortId = null;

    public string $course_id = '';

    public string $code = '';

    public string $title_override_ar = '';

    public string $delivery_mode = 'onsite';

    public string $venue_ar = '';

    public string $venue_url = '';

    public string $city_ar = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public string $capacity = '';

    public string $price = '0.000';

    public bool $is_free = false;

    public string $client_id = '';

    public string $invoicing_entity_id = '';

    public string $registration_opens_at = '';

    public string $registration_closes_at = '';

    public string $internal_notes = '';

    public function mount(?int $cohort = null): void
    {
        if ($cohort !== null) {
            $model = Cohort::query()->findOrFail($cohort);
            $this->authorize('update', $model);

            $this->cohortId = $model->id;
            $this->course_id = (string) $model->course_id;
            $this->code = $model->code;
            $this->title_override_ar = (string) $model->title_override_ar;
            $this->delivery_mode = $model->delivery_mode->value;
            $this->venue_ar = (string) $model->venue_ar;
            $this->venue_url = (string) $model->venue_url;
            $this->city_ar = (string) $model->city_ar;
            $this->starts_at = $this->toLocalInput($model->starts_at);
            $this->ends_at = $this->toLocalInput($model->ends_at);
            $this->capacity = $model->capacity !== null ? (string) $model->capacity : '';
            $this->price = Baisa::toString($model->price_baisa);
            $this->is_free = $model->is_free;
            $this->client_id = $model->client_id !== null ? (string) $model->client_id : '';
            $this->invoicing_entity_id = (string) $model->invoicing_entity_id;
            $this->registration_opens_at = $this->toLocalInput($model->registration_opens_at);
            $this->registration_closes_at = $this->toLocalInput($model->registration_closes_at);
            $this->internal_notes = (string) $model->internal_notes;
        } else {
            $this->authorize('create', Cohort::class);

            $this->invoicing_entity_id = (string) InvoicingEntity::query()->where('is_default', true)->value('id');
        }
    }

    public function save(): void
    {
        if ($this->cohortId !== null) {
            $cohort = Cohort::query()->findOrFail($this->cohortId);
            $this->authorize('update', $cohort);
        } else {
            $cohort = null;
            $this->authorize('create', Cohort::class);
        }

        $validated = $this->validate([
            'course_id' => ['required', Rule::exists('courses', 'id')],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-]+$/', Rule::unique('cohorts', 'code')->ignore($this->cohortId)],
            'title_override_ar' => ['nullable', 'string', 'max:255'],
            'delivery_mode' => ['required', Rule::enum(DeliveryMode::class)],
            'venue_ar' => ['nullable', 'string', 'max:255'],
            'venue_url' => ['nullable', 'url', 'max:500'],
            'city_ar' => ['nullable', 'string', 'max:100'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'price' => ['required', 'regex:'.Baisa::INPUT_PATTERN],
            'is_free' => ['boolean'],
            'client_id' => ['nullable', Rule::exists('clients', 'id')],
            'invoicing_entity_id' => ['required', Rule::exists('invoicing_entities', 'id')],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'ends_at.after' => __('courses.ends_after_starts'),
        ]);

        $attributes = [
            'course_id' => (int) $validated['course_id'],
            'code' => $validated['code'] ?: $this->generateCode((int) $validated['course_id']),
            'title_override_ar' => $validated['title_override_ar'] ?: null,
            'delivery_mode' => $validated['delivery_mode'],
            'venue_ar' => $validated['venue_ar'] ?: null,
            'venue_url' => $validated['venue_url'] ?: null,
            'city_ar' => $validated['city_ar'] ?: null,
            'starts_at' => $this->fromLocalInput($validated['starts_at']),
            'ends_at' => $this->fromLocalInput($validated['ends_at']),
            'capacity' => $validated['capacity'] !== null && $validated['capacity'] !== '' ? (int) $validated['capacity'] : null,
            'price_baisa' => $this->is_free ? 0 : Baisa::fromString($validated['price']),
            'is_free' => $this->is_free,
            'client_id' => $validated['client_id'] !== null && $validated['client_id'] !== '' ? (int) $validated['client_id'] : null,
            'invoicing_entity_id' => (int) $validated['invoicing_entity_id'],
            'registration_opens_at' => $validated['registration_opens_at'] ? $this->fromLocalInput($validated['registration_opens_at']) : null,
            'registration_closes_at' => $validated['registration_closes_at'] ? $this->fromLocalInput($validated['registration_closes_at']) : null,
            'internal_notes' => $validated['internal_notes'] ?: null,
        ];

        if ($cohort !== null) {
            $cohort->update($attributes);
        } else {
            $cohort = Cohort::query()->create($attributes);
        }

        $this->dispatch('toast', type: 'success', message: __('courses.cohort_saved'));
        $this->redirectRoute('admin.cohorts.show', ['cohort' => $cohort->id], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.catalog.cohort-form', [
            'courses' => Course::query()->orderBy('title_ar')->get(['id', 'title_ar']),
            'clients' => Client::query()->orderBy('name_ar')->get(['id', 'name_ar']),
            'entities' => InvoicingEntity::query()->orderBy('name_ar')->get(['id', 'name_ar']),
            'modes' => DeliveryMode::cases(),
        ])->title($this->cohortId ? __('courses.edit_cohort') : __('courses.new_cohort'));
    }

    /**
     * Suggested code: uppercase course-slug prefix + year-month, unique.
     */
    protected function generateCode(int $courseId): string
    {
        $slug = (string) Course::query()->whereKey($courseId)->value('slug');
        $prefix = Str::of($slug)->explode('-')
            ->reject(fn ($part) => is_numeric($part))
            ->take(2)
            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 4)))
            ->implode('-');
        $prefix = $prefix !== '' ? $prefix : 'CO';

        $base = $prefix.'-'.now()->timezone(config('app.display_timezone'))->format('Y-m');
        $candidate = $base;
        $suffix = 2;

        while (Cohort::query()->withTrashed()->where('code', $candidate)->exists()) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    protected function toLocalInput(?CarbonInterface $utc): string
    {
        return $utc?->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i') ?? '';
    }

    protected function fromLocalInput(string $local): Carbon
    {
        return Carbon::parse($local, config('app.display_timezone'))->utc();
    }
}
