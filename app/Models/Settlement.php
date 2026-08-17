<?php

namespace App\Models;

use App\Enums\SettlementStatus;
use App\Enums\SettlementType;
use App\Finance\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'settlement_number', 'type', 'cohort_id', 'period_start', 'period_end',
    'gross_revenue_baisa', 'direct_costs_baisa', 'net_distributable_baisa',
    'deliverer_total_baisa', 'center_share_baisa',
    'center_opex_allocated_baisa', 'distributable_profit_baisa', 'status',
    'journal_entry_id', 'computed_at', 'confirmed_by', 'confirmed_at',
    'notes_ar', 'snapshot',
])]
class Settlement extends Model
{
    use LogsActivity, SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'type' => SettlementType::class,
        'status' => SettlementStatus::class,
        'period_start' => 'date',
        'period_end' => 'date',
        'gross_revenue_baisa' => MoneyCast::class,
        'direct_costs_baisa' => MoneyCast::class,
        'net_distributable_baisa' => MoneyCast::class,
        'deliverer_total_baisa' => MoneyCast::class,
        'center_share_baisa' => MoneyCast::class,
        'center_opex_allocated_baisa' => MoneyCast::class,
        'distributable_profit_baisa' => MoneyCast::class,
        'computed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'snapshot' => 'array',
    ];

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('settlements')
            ->logOnly(['settlement_number', 'status', 'net_distributable_baisa', 'journal_entry_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
