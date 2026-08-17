<?php

namespace App\Livewire\Admin\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Registration\RegisterParticipant;
use App\Support\Baisa;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.admin')]
class EnrollmentsIndex extends Component
{
    use WithPagination;

    #[Locked]
    public int $cohortId;

    #[Locked]
    public ?int $cancellingId = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    /** @var array<int, int> */
    public array $selected = [];

    public function mount(int $cohort): void
    {
        $this->authorize('viewAny', Enrollment::class);
        $this->cohortId = Cohort::query()->findOrFail($cohort)->id;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $this->authorize('manage', Enrollment::class);

        $enrollment = $this->findEnrollment($id);

        app(RegisterParticipant::class)->approve($enrollment, (int) auth()->id());

        $this->dispatch('toast', type: 'success', message: __('courses.enrollment_approved'));
    }

    public function bulkApprove(): void
    {
        $this->authorize('manage', Enrollment::class);

        $count = 0;
        $service = app(RegisterParticipant::class);

        foreach ($this->selected as $id) {
            $enrollment = Enrollment::query()
                ->where('cohort_id', $this->cohortId)
                ->find($id);

            if ($enrollment !== null && in_array($enrollment->status, [EnrollmentStatus::Pending, EnrollmentStatus::Waitlisted], true)) {
                $service->approve($enrollment, (int) auth()->id());
                $count++;
            }
        }

        $this->selected = [];
        $this->dispatch('toast', type: 'success', message: __('courses.enrollments_approved', ['count' => $count]));
    }

    public function confirmCancel(int $id): void
    {
        $this->authorize('manage', Enrollment::class);
        $this->cancellingId = $this->findEnrollment($id)->id;
    }

    public function cancelCancel(): void
    {
        $this->cancellingId = null;
    }

    public function cancelEnrollment(): void
    {
        if ($this->cancellingId === null) {
            return;
        }

        $this->authorize('manage', Enrollment::class);

        app(RegisterParticipant::class)->cancel($this->findEnrollment($this->cancellingId));

        $this->cancellingId = null;
        $this->dispatch('toast', type: 'success', message: __('courses.enrollment_cancelled'));
    }

    public function export(): StreamedResponse
    {
        $this->authorize('export', Enrollment::class);

        $cohort = Cohort::query()->with('course:id,title_ar')->findOrFail($this->cohortId);

        $rows = Enrollment::query()
            ->where('cohort_id', $this->cohortId)
            ->orderBy('enrolled_at')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $writer = SimpleExcelWriter::streamDownload('enrollments.xlsx');

            foreach ($rows as $enrollment) {
                $writer->addRow([
                    'الاسم' => $enrollment->full_name_ar,
                    'البريد' => $enrollment->email,
                    'الهاتف' => $enrollment->phone,
                    'الجهة' => $enrollment->organization_ar ?? '',
                    'الحالة' => $enrollment->status->label(),
                    'المستحق (ر.ع.)' => Baisa::toString($enrollment->amount_due_baisa),
                    'المدفوع (ر.ع.)' => Baisa::toString($enrollment->amount_paid_baisa),
                    'حالة الدفع' => $enrollment->payment_status->label(),
                    'تاريخ التسجيل' => $enrollment->enrolled_at->timezone(config('app.display_timezone'))->format('Y-m-d H:i'),
                ]);
            }

            $writer->close();
        }, 'enrollments-'.$cohort->code.'.xlsx');
    }

    public function render(): View
    {
        $cohort = Cohort::query()->with('course:id,title_ar')->findOrFail($this->cohortId);

        $enrollments = Enrollment::query()
            ->where('cohort_id', $this->cohortId)
            ->when($this->search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('full_name_ar', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('enrolled_at')
            ->simplePaginate(20);

        return view('livewire.admin.enrollments.enrollments-index', [
            'cohort' => $cohort,
            'enrollments' => $enrollments,
            'statuses' => EnrollmentStatus::cases(),
            'cancellingEnrollment' => $this->cancellingId !== null
                ? Enrollment::query()->find($this->cancellingId)
                : null,
        ])->title(__('courses.enrollments').' — '.$cohort->code);
    }

    protected function findEnrollment(int $id): Enrollment
    {
        return Enrollment::query()
            ->where('cohort_id', $this->cohortId)
            ->findOrFail($id);
    }
}
