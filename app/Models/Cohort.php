<?php

namespace App\Models;

use App\Enums\CohortStatus;
use App\Enums\DeliveryMode;
use App\Exceptions\InvalidCohortTransition;
use Database\Factories\CohortFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'course_id', 'code', 'title_override_ar', 'delivery_mode', 'venue_ar',
    'venue_url', 'city_ar', 'starts_at', 'ends_at', 'timezone', 'capacity',
    'price_baisa', 'is_free', 'client_id', 'invoicing_entity_id',
    'distribution_policy_id', 'registration_opens_at',
    'registration_closes_at', 'internal_notes',
])]
class Cohort extends Model
{
    /** @use HasFactory<CohortFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'delivery_mode' => DeliveryMode::class,
        'status' => CohortStatus::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'capacity' => 'integer',
        'seats_taken' => 'integer',
        'price_baisa' => 'integer',
        'is_free' => 'boolean',
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<InvoicingEntity, $this>
     */
    public function invoicingEntity(): BelongsTo
    {
        return $this->belongsTo(InvoicingEntity::class);
    }

    /**
     * @return HasMany<CohortSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(CohortSession::class)->orderBy('session_number');
    }

    /**
     * @return HasMany<CohortDeliverer, $this>
     */
    public function deliverers(): HasMany
    {
        return $this->hasMany(CohortDeliverer::class);
    }

    /**
     * The only way status ever changes. Invalid transitions throw (Phase 2
     * acceptance) and every change lands in the activity log.
     */
    public function transitionTo(CohortStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidCohortTransition::between($this->status, $target);
        }

        $this->status = $target;
        $this->save();
    }

    public function displayTitle(): string
    {
        return $this->title_override_ar ?? $this->course->title_ar;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('cohorts')
            ->logOnly([
                'code', 'status', 'starts_at', 'ends_at', 'capacity',
                'price_baisa', 'is_free', 'client_id', 'invoicing_entity_id',
                'distribution_policy_id',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
