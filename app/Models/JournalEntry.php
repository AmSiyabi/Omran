<?php

namespace App\Models;

use App\Enums\JournalEntryStatus;
use App\Observers\JournalEntryObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Append-only (spec §8.3): no updated_at, no soft deletes. The observer
 * blocks every mutation except the posted→reversed status flip.
 */
#[Fillable([
    'entry_number', 'entry_date', 'description_ar', 'reference_type',
    'reference_id', 'cohort_id', 'invoicing_entity_id', 'status',
    'reversed_by_entry_id', 'created_by',
])]
#[ObservedBy(JournalEntryObserver::class)]
class JournalEntry extends Model
{
    use LogsActivity;

    public const UPDATED_AT = null;

    /** @var array<string, string> */
    protected $casts = [
        'entry_date' => 'date',
        'status' => JournalEntryStatus::class,
        'created_at' => 'datetime',
    ];

    /**
     * @return HasMany<JournalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('line_order');
    }

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * @return BelongsTo<InvoicingEntity, $this>
     */
    public function invoicingEntity(): BelongsTo
    {
        return $this->belongsTo(InvoicingEntity::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_entry_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('journal')
            ->logOnly(['entry_number', 'status', 'reversed_by_entry_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
