<?php

namespace App\Livewire\Admin\Catalog;

use App\Enums\CohortStatus;
use App\Enums\DelivererType;
use App\Exceptions\InvalidCohortTransition;
use App\Models\Cohort;
use App\Models\Instructor;
use App\Models\Partner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class CohortShow extends Component
{
    #[Locked]
    public int $cohortId;

    // ── حالة الدفعة ─────────────────────────────────────────────
    public ?string $pendingTransition = null;

    // ── الجلسات ────────────────────────────────────────────────
    #[Locked]
    public ?int $editingSessionId = null;

    #[Locked]
    public ?int $deletingSessionId = null;

    public bool $showSessionForm = false;

    public string $session_title = '';

    public string $session_starts_at = '';

    public string $session_ends_at = '';

    public string $session_venue = '';

    public string $session_notes = '';

    // ── المنفذون (تُحرر كمجموعة واحدة، المجموع = 100) ───────────
    public bool $showDeliverersForm = false;

    /** @var array<int, array{type: string, partner_id: string, instructor_id: string, weight: string}> */
    public array $delivererRows = [];

    public function mount(int $cohort): void
    {
        $model = Cohort::query()->findOrFail($cohort);
        $this->authorize('view', $model);
        $this->cohortId = $model->id;
    }

    // ── الانتقالات ──────────────────────────────────────────────

    public function confirmTransition(string $target): void
    {
        $cohort = $this->cohort();
        $this->authorize('transition', $cohort);

        $status = CohortStatus::from($target);

        if (! $cohort->status->canTransitionTo($status) || $status === CohortStatus::Settled) {
            $this->dispatch('toast', type: 'error', message: __('courses.invalid_transition'));

            return;
        }

        $this->pendingTransition = $status->value;
    }

    public function cancelTransition(): void
    {
        $this->pendingTransition = null;
    }

    public function applyTransition(): void
    {
        if ($this->pendingTransition === null) {
            return;
        }

        $cohort = $this->cohort();
        $this->authorize('transition', $cohort);

        $target = CohortStatus::from($this->pendingTransition);
        $this->pendingTransition = null;

        // التصفية تتم حصرياً عبر محرك التوزيع (المرحلة 5)
        if ($target === CohortStatus::Settled) {
            $this->dispatch('toast', type: 'error', message: __('courses.invalid_transition'));

            return;
        }

        try {
            $cohort->transitionTo($target);
        } catch (InvalidCohortTransition) {
            $this->dispatch('toast', type: 'error', message: __('courses.invalid_transition'));

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('courses.status_changed', ['status' => $target->label()]));
    }

    // ── الجلسات ────────────────────────────────────────────────

    public function createSession(): void
    {
        $this->authorize('update', $this->cohort());

        $this->reset('editingSessionId', 'session_title', 'session_venue', 'session_notes');
        $this->session_starts_at = '';
        $this->session_ends_at = '';
        $this->resetValidation();
        $this->showSessionForm = true;
    }

    public function editSession(int $sessionId): void
    {
        $cohort = $this->cohort();
        $this->authorize('update', $cohort);

        $session = $cohort->sessions()->findOrFail($sessionId);

        $this->editingSessionId = $session->id;
        $this->session_title = (string) $session->title_ar;
        $this->session_starts_at = $session->starts_at->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i');
        $this->session_ends_at = $session->ends_at->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i');
        $this->session_venue = (string) $session->venue_override_ar;
        $this->session_notes = (string) $session->notes;
        $this->resetValidation();
        $this->showSessionForm = true;
    }

    public function saveSession(): void
    {
        $cohort = $this->cohort();
        $this->authorize('update', $cohort);

        $validated = $this->validate([
            'session_title' => ['nullable', 'string', 'max:255'],
            'session_starts_at' => ['required', 'date'],
            'session_ends_at' => ['required', 'date', 'after:session_starts_at'],
            'session_venue' => ['nullable', 'string', 'max:255'],
            'session_notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'session_ends_at.after' => __('courses.ends_after_starts'),
        ]);

        $attributes = [
            'title_ar' => $validated['session_title'] ?: null,
            'starts_at' => Carbon::parse($validated['session_starts_at'], config('app.display_timezone'))->utc(),
            'ends_at' => Carbon::parse($validated['session_ends_at'], config('app.display_timezone'))->utc(),
            'venue_override_ar' => $validated['session_venue'] ?: null,
            'notes' => $validated['session_notes'] ?: null,
        ];

        if ($this->editingSessionId !== null) {
            $cohort->sessions()->findOrFail($this->editingSessionId)->update($attributes);
        } else {
            $attributes['session_number'] = (int) $cohort->sessions()->max('session_number') + 1;
            $cohort->sessions()->create($attributes);
        }

        $this->showSessionForm = false;
        $this->dispatch('toast', type: 'success', message: __('courses.session_saved'));
    }

    public function closeSessionForm(): void
    {
        $this->showSessionForm = false;
    }

    public function confirmDeleteSession(int $sessionId): void
    {
        $cohort = $this->cohort();
        $this->authorize('update', $cohort);

        $this->deletingSessionId = $cohort->sessions()->findOrFail($sessionId)->id;
    }

    public function cancelDeleteSession(): void
    {
        $this->deletingSessionId = null;
    }

    public function deleteSession(): void
    {
        if ($this->deletingSessionId === null) {
            return;
        }

        $cohort = $this->cohort();
        $this->authorize('update', $cohort);

        $cohort->sessions()->findOrFail($this->deletingSessionId)->delete();
        $this->deletingSessionId = null;
        $this->dispatch('toast', type: 'success', message: __('courses.session_deleted'));
    }

    // ── المنفذون ────────────────────────────────────────────────

    public function editDeliverers(): void
    {
        $cohort = $this->cohort();
        $this->authorize('update', $cohort);

        $this->delivererRows = $cohort->deliverers()->get()
            ->map(fn ($deliverer) => [
                'type' => $deliverer->deliverer_type->value,
                'partner_id' => $deliverer->partner_id !== null ? (string) $deliverer->partner_id : '',
                'instructor_id' => $deliverer->instructor_id !== null ? (string) $deliverer->instructor_id : '',
                'weight' => rtrim(rtrim((string) $deliverer->share_weight, '0'), '.'),
            ])
            ->values()
            ->all();

        if ($this->delivererRows === []) {
            $this->delivererRows = [['type' => 'partner', 'partner_id' => '', 'instructor_id' => '', 'weight' => '100']];
        }

        $this->resetValidation();
        $this->showDeliverersForm = true;
    }

    public function addDelivererRow(): void
    {
        $this->authorize('update', $this->cohort());
        $this->delivererRows[] = ['type' => 'partner', 'partner_id' => '', 'instructor_id' => '', 'weight' => ''];
    }

    public function removeDelivererRow(int $index): void
    {
        $this->authorize('update', $this->cohort());

        unset($this->delivererRows[$index]);
        $this->delivererRows = array_values($this->delivererRows);
    }

    public function saveDeliverers(): void
    {
        $cohort = $this->cohort();
        $this->authorize('update', $cohort);

        $this->validate([
            'delivererRows' => ['required', 'array', 'min:1'],
            'delivererRows.*.type' => ['required', Rule::enum(DelivererType::class)],
            'delivererRows.*.weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'delivererRows.*.partner_id' => ['nullable', Rule::exists('partners', 'id')],
            'delivererRows.*.instructor_id' => ['nullable', Rule::exists('instructors', 'id')],
        ]);

        // كل صف يجب أن يحدد شريكاً أو مدرباً بحسب نوعه
        foreach ($this->delivererRows as $index => $row) {
            $hasIdentity = $row['type'] === 'partner' ? $row['partner_id'] !== '' : $row['instructor_id'] !== '';

            if (! $hasIdentity) {
                throw ValidationException::withMessages([
                    "delivererRows.{$index}.type" => __('courses.deliverer_required'),
                ]);
            }
        }

        // spec §7.2: الأوزان يجب أن تساوي 100.00 بالضبط — حساب بالبيسة المئوية
        // (سنت النسبة) بدون أي أعداد عشرية عائمة
        $totalHundredths = 0;
        foreach ($this->delivererRows as $row) {
            $totalHundredths += (int) round(((float) $row['weight']) * 100);
        }

        if ($totalHundredths !== 10000) {
            throw ValidationException::withMessages([
                'delivererRows' => __('courses.weights_must_sum_100'),
            ]);
        }

        DB::transaction(function () use ($cohort): void {
            $cohort->deliverers()->delete();

            foreach ($this->delivererRows as $row) {
                $cohort->deliverers()->create([
                    'deliverer_type' => $row['type'],
                    'partner_id' => $row['type'] === 'partner' ? (int) $row['partner_id'] : null,
                    'instructor_id' => $row['type'] === 'external' ? (int) $row['instructor_id'] : null,
                    'share_weight' => $row['weight'],
                ]);
            }
        });

        $this->showDeliverersForm = false;
        $this->dispatch('toast', type: 'success', message: __('courses.deliverers_saved'));
    }

    public function closeDeliverersForm(): void
    {
        $this->showDeliverersForm = false;
    }

    public function render(): View
    {
        $cohort = Cohort::query()
            ->with([
                'course:id,title_ar,slug',
                'client:id,name_ar',
                'invoicingEntity:id,name_ar',
                'sessions',
                'deliverers.partner:id,display_name_ar',
                'deliverers.instructor:id,name_ar',
            ])
            ->findOrFail($this->cohortId);

        return view('livewire.admin.catalog.cohort-show', [
            'cohort' => $cohort,
            'availableTransitions' => array_filter(
                $cohort->status->allowedTransitions(),
                fn (CohortStatus $status) => $status !== CohortStatus::Settled,
            ),
            'partners' => Partner::query()->where('is_active', true)->get(['id', 'display_name_ar']),
            'instructorOptions' => Instructor::query()->orderBy('name_ar')->get(['id', 'name_ar']),
        ])->title($cohort->displayTitle());
    }

    protected function cohort(): Cohort
    {
        return Cohort::query()->findOrFail($this->cohortId);
    }
}
