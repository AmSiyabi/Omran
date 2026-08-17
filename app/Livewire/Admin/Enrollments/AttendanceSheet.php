<?php

namespace App\Livewire\Admin\Enrollments;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Models\AttendanceRecord;
use App\Models\Cohort;
use App\Models\Enrollment;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Phone-in-classroom sheet: one tap toggles a participant's status and saves
 * immediately (spec target: 30 participants in under 90 seconds).
 */
#[Layout('components.layouts.admin')]
class AttendanceSheet extends Component
{
    #[Locked]
    public int $cohortId;

    #[Locked]
    public ?int $sessionId = null;

    public function mount(int $cohort): void
    {
        $this->authorize('manage', Enrollment::class);

        $model = Cohort::query()->with('sessions')->findOrFail($cohort);
        $this->cohortId = $model->id;
        $this->sessionId = $model->sessions->first()?->id;
    }

    public function selectSession(int $sessionId): void
    {
        $this->authorize('manage', Enrollment::class);

        $this->sessionId = Cohort::query()
            ->findOrFail($this->cohortId)
            ->sessions()
            ->findOrFail($sessionId)
            ->id;
    }

    public function toggle(int $enrollmentId): void
    {
        $this->authorize('manage', Enrollment::class);

        if ($this->sessionId === null) {
            return;
        }

        $enrollment = $this->attendableEnrollments()->firstWhere('id', $enrollmentId);

        if ($enrollment === null) {
            return;
        }

        $record = AttendanceRecord::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('cohort_session_id', $this->sessionId)
            ->first();

        if ($record === null) {
            // أول نقرة: حاضر
            AttendanceRecord::query()->create([
                'enrollment_id' => $enrollment->id,
                'cohort_session_id' => $this->sessionId,
                'status' => AttendanceStatus::Present,
                'marked_at' => now(),
                'marked_by' => auth()->id(),
            ]);

            return;
        }

        $record->update([
            'status' => $record->status->next(),
            'marked_at' => now(),
            'marked_by' => auth()->id(),
        ]);
    }

    public function markAllPresent(): void
    {
        $this->authorize('manage', Enrollment::class);

        if ($this->sessionId === null) {
            return;
        }

        foreach ($this->attendableEnrollments() as $enrollment) {
            AttendanceRecord::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'cohort_session_id' => $this->sessionId,
                ],
                [
                    'status' => AttendanceStatus::Present,
                    'marked_at' => now(),
                    'marked_by' => auth()->id(),
                ],
            );
        }

        $this->dispatch('toast', type: 'success', message: __('courses.attendance_saved'));
    }

    public function render(): View
    {
        $cohort = Cohort::query()
            ->with(['course:id,title_ar', 'sessions'])
            ->findOrFail($this->cohortId);

        $enrollments = $this->attendableEnrollments();

        $records = $this->sessionId === null
            ? collect()
            : AttendanceRecord::query()
                ->where('cohort_session_id', $this->sessionId)
                ->whereIn('enrollment_id', $enrollments->pluck('id'))
                ->get()
                ->keyBy('enrollment_id');

        return view('livewire.admin.enrollments.attendance-sheet', [
            'cohort' => $cohort,
            'enrollments' => $enrollments,
            'records' => $records,
        ])->title(__('courses.attendance_sheet').' — '.$cohort->code);
    }

    /**
     * @return Collection<int, Enrollment>
     */
    protected function attendableEnrollments(): Collection
    {
        return Enrollment::query()
            ->where('cohort_id', $this->cohortId)
            ->whereIn('status', [EnrollmentStatus::Confirmed, EnrollmentStatus::Attended, EnrollmentStatus::NoShow])
            ->orderBy('full_name_ar')
            ->get();
    }
}
