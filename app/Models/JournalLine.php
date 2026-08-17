<?php

namespace App\Models;

use App\Finance\MoneyCast;
use App\Observers\JournalLineObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only (spec §8.3): lines are never updated or deleted, ever.
 */
#[Fillable([
    'journal_entry_id', 'account_id', 'debit_baisa', 'credit_baisa',
    'partner_id', 'cohort_id', 'memo_ar', 'line_order',
])]
#[ObservedBy(JournalLineObserver::class)]
class JournalLine extends Model
{
    public const UPDATED_AT = null;

    /** @var array<string, string> */
    protected $casts = [
        'debit_baisa' => MoneyCast::class,
        'credit_baisa' => MoneyCast::class,
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }
}
