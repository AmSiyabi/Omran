<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'user_id', 'display_name_ar', 'display_name_en', 'bio_ar', 'bio_en', 'photo_path',
    'ownership_percent', 'effective_from', 'effective_to', 'is_active',
    'public_profile_visible', 'bank_name', 'bank_account', 'civil_number',
])]
class Partner extends Model
{
    use LogsActivity, SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'ownership_percent' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'public_profile_visible' => 'boolean',
        // PII at rest is encrypted (spec §10)
        'bank_name' => 'encrypted',
        'bank_account' => 'encrypted',
        'civil_number' => 'encrypted',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Encrypted PII stays out of the audit trail — logging it would
        // write the decrypted values into activity_log in plaintext.
        return LogOptions::defaults()
            ->useLogName('partners')
            ->logOnly([
                'display_name_ar', 'display_name_en', 'ownership_percent',
                'effective_from', 'effective_to', 'is_active', 'public_profile_visible',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
