<?php

namespace App\Models;

use App\Enums\DelivererType;
use App\Observers\CohortDelivererObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cohort_id', 'deliverer_type', 'partner_id', 'instructor_id', 'share_weight'])]
#[ObservedBy(CohortDelivererObserver::class)]
class CohortDeliverer extends Model
{
    /** @var array<string, string> */
    protected $casts = [
        'deliverer_type' => DelivererType::class,
        'share_weight' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * @return BelongsTo<Instructor, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function displayName(): string
    {
        return $this->deliverer_type === DelivererType::Partner
            ? $this->partner->display_name_ar
            : $this->instructor->name_ar;
    }
}
