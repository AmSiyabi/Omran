<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'code', 'name_ar', 'description_ar', 'deliverer_share_percent',
    'external_fee_mode', 'deduct_direct_costs_first', 'center_split_mode',
    'is_active', 'effective_from', 'effective_to', 'version',
])]
class DistributionPolicy extends Model
{
    use LogsActivity, SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'deliverer_share_percent' => 'decimal:2',
        'deduct_direct_costs_first' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'version' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('distribution_policies')
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
