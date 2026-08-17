<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name_ar', 'name_en', 'type', 'partner_id', 'is_system', 'is_active'])]
class Account extends Model
{
    use SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'type' => AccountType::class,
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public static function byCode(string $code): self
    {
        return self::query()->where('code', $code)->firstOrFail();
    }
}
