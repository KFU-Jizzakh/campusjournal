<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * PURPOSE: Key-value configuration store with caching,
 * used for site-wide settings editable via Filament admin.
 */
#[WithoutTimestamps]
#[WithoutIncrementing]
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever("settings.{$key}", function () use ($key, $default) {
            return static::find($key)?->value ?? $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("settings.{$key}");
    }
}
