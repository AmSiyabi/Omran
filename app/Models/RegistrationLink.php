<?php

namespace App\Models;

use Database\Factories\RegistrationLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'cohort_id', 'token', 'label_ar', 'price_override_baisa', 'max_uses',
    'expires_at', 'requires_approval', 'is_active', 'created_by',
])]
class RegistrationLink extends Model
{
    /** @use HasFactory<RegistrationLinkFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'price_override_baisa' => 'integer',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'expires_at' => 'datetime',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Spec §7.3/§10: 32-char base62 token from random_bytes — ~190 bits of
     * entropy, never sequential, never guessable.
     */
    public static function generateToken(): string
    {
        $token = '';
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        foreach (str_split(random_bytes(32)) as $byte) {
            $token .= $alphabet[ord($byte) % 62];
        }

        return $token;
    }

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_uses !== null && $this->uses_count >= $this->max_uses;
    }

    public function url(): string
    {
        return route('public.join', $this->token);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('registration_links')
            ->logOnly(['label_ar', 'price_override_baisa', 'max_uses', 'expires_at', 'requires_approval', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
