<?php

namespace App\Registration;

use App\Enums\CohortStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\LinkNotUsable;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\RegistrationLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only write-path into enrollments from the public site.
 *
 * Concurrency safety (spec Phase 4): the cohort and link rows are locked
 * FOR UPDATE inside one transaction, so the seat counter and uses counter
 * can never oversell — 50 simultaneous registrations against 10 seats yield
 * exactly 10 confirmed and 40 waitlisted.
 */
class RegisterParticipant
{
    /**
     * @param  array{full_name_ar: string, email: string, phone: string, organization_ar?: ?string, job_title_ar?: ?string}  $data
     */
    public function handle(RegistrationLink $link, array $data): Enrollment
    {
        return DB::transaction(function () use ($link, $data): Enrollment {
            /** @var RegistrationLink $link */
            $link = RegistrationLink::query()
                ->whereKey($link->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Cohort $cohort */
            $cohort = Cohort::query()
                ->whereKey($link->cohort_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertUsable($link, $cohort);

            // unique (cohort, email) — race-safe because of the cohort lock
            $emailTaken = Enrollment::query()
                ->where('cohort_id', $cohort->id)
                ->where('email', mb_strtolower(trim($data['email'])))
                ->exists();

            if ($emailTaken) {
                throw ValidationException::withMessages([
                    'email' => __('courses.join_email_taken'),
                ]);
            }

            $status = $this->resolveStatus($link, $cohort);

            if ($status->holdsSeat()) {
                $cohort->seats_taken += 1;
                $cohort->save();
            }

            $link->uses_count += 1;
            $link->save();

            $amountDue = $cohort->is_free
                ? 0
                : ($link->price_override_baisa ?? $cohort->price_baisa);

            return Enrollment::query()->create([
                'cohort_id' => $cohort->id,
                'registration_link_id' => $link->id,
                'full_name_ar' => trim($data['full_name_ar']),
                'email' => mb_strtolower(trim($data['email'])),
                'phone' => trim($data['phone']),
                'organization_ar' => $data['organization_ar'] ?? null,
                'job_title_ar' => $data['job_title_ar'] ?? null,
                'status' => $status,
                'amount_due_baisa' => $amountDue,
                'payment_status' => $amountDue === 0 ? PaymentStatus::Waived : PaymentStatus::Unpaid,
                'enrolled_at' => now(),
            ]);
        });
    }

    /**
     * Approve a pending enrollment — seat is only granted here (D-033),
     * with the same lock discipline, so approval can never oversell either.
     */
    public function approve(Enrollment $enrollment, int $approvedBy): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $approvedBy): Enrollment {
            /** @var Enrollment $enrollment */
            $enrollment = Enrollment::query()
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($enrollment->status, [EnrollmentStatus::Pending, EnrollmentStatus::Waitlisted], true)) {
                return $enrollment;
            }

            /** @var Cohort $cohort */
            $cohort = Cohort::query()
                ->whereKey($enrollment->cohort_id)
                ->lockForUpdate()
                ->firstOrFail();

            $hasSeat = $cohort->capacity === null || $cohort->seats_taken < $cohort->capacity;

            if ($hasSeat) {
                $cohort->seats_taken += 1;
                $cohort->save();

                $enrollment->status = EnrollmentStatus::Confirmed;
            } else {
                $enrollment->status = EnrollmentStatus::Waitlisted;
            }

            $enrollment->approved_at = now();
            $enrollment->approved_by = $approvedBy;
            $enrollment->save();

            return $enrollment;
        });
    }

    /**
     * Cancel an enrollment, releasing its seat when it held one.
     */
    public function cancel(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment): Enrollment {
            /** @var Enrollment $enrollment */
            $enrollment = Enrollment::query()
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($enrollment->status === EnrollmentStatus::Cancelled) {
                return $enrollment;
            }

            if ($enrollment->status->holdsSeat()) {
                Cohort::query()
                    ->whereKey($enrollment->cohort_id)
                    ->lockForUpdate()
                    ->decrement('seats_taken');
            }

            $enrollment->status = EnrollmentStatus::Cancelled;
            $enrollment->save();

            return $enrollment;
        });
    }

    /**
     * Read-only usability check for the GET page (no locks, no writes).
     */
    public function probe(RegistrationLink $link): void
    {
        $this->assertUsable($link, $link->cohort);
    }

    protected function assertUsable(RegistrationLink $link, Cohort $cohort): void
    {
        if (! $link->is_active) {
            throw LinkNotUsable::inactive();
        }

        if ($link->isExpired()) {
            throw LinkNotUsable::expired();
        }

        if ($link->isExhausted()) {
            throw LinkNotUsable::exhausted();
        }

        if ($cohort->status !== CohortStatus::Open) {
            throw LinkNotUsable::registrationClosed();
        }

        if ($cohort->registration_opens_at !== null && $cohort->registration_opens_at->isFuture()) {
            throw LinkNotUsable::registrationClosed();
        }

        if ($cohort->registration_closes_at !== null && $cohort->registration_closes_at->isPast()) {
            throw LinkNotUsable::registrationClosed();
        }
    }

    protected function resolveStatus(RegistrationLink $link, Cohort $cohort): EnrollmentStatus
    {
        // Approval-required links never hold a seat at submission time —
        // the seat decision happens at approval (D-033).
        if ($link->requires_approval) {
            return EnrollmentStatus::Pending;
        }

        $hasSeat = $cohort->capacity === null || $cohort->seats_taken < $cohort->capacity;

        return $hasSeat ? EnrollmentStatus::Confirmed : EnrollmentStatus::Waitlisted;
    }
}
