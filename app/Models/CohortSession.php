<?php

namespace App\Models;

use Database\Factories\CohortSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'cohort_id', 'session_number', 'title_ar', 'starts_at', 'ends_at',
    'venue_override_ar', 'notes',
])]
class CohortSession extends Model
{
    /** @use HasFactory<CohortSessionFactory> */
    use HasFactory, SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'session_number' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }
}
