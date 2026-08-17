<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'cohort_id', 'registration_link_id', 'full_name_ar', 'email', 'phone',
    'organization_ar', 'job_title_ar', 'status', 'amount_due_baisa',
    'amount_paid_baisa', 'payment_status', 'notes', 'enrolled_at',
    'approved_at', 'approved_by',
])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory, SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'status' => EnrollmentStatus::class,
        'payment_status' => PaymentStatus::class,
        'amount_due_baisa' => 'integer',
        'amount_paid_baisa' => 'integer',
        'enrolled_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * @return BelongsTo<RegistrationLink, $this>
     */
    public function registrationLink(): BelongsTo
    {
        return $this->belongsTo(RegistrationLink::class);
    }

    /**
     * @return HasMany<AttendanceRecord, $this>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
