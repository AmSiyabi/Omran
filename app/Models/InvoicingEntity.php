<?php

namespace App\Models;

use Database\Factories\InvoicingEntityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'name_ar', 'name_en', 'type', 'cr_number', 'tax_card_number', 'vat_number',
    'vat_registered', 'vat_registered_from', 'is_default', 'notes',
])]
class InvoicingEntity extends Model
{
    /** @use HasFactory<InvoicingEntityFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'vat_registered' => 'boolean',
        'vat_registered_from' => 'date',
        'is_default' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('invoicing_entities')
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
