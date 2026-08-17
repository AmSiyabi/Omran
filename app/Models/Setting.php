<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'array',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::query()->find($key);

        return $setting !== null ? $setting->value : $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
