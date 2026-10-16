<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('store-settings'));
        static::deleted(fn () => Cache::forget('store-settings'));
    }

    public static function valueOf(string $key, mixed $default = null): mixed
    {
        return static::allAsArray()[$key] ?? $default;
    }

    /** @return array<string, string|null> */
    public static function allAsArray(): array
    {
        return Cache::rememberForever('store-settings', function (): array {
            return static::query()->pluck('value', 'key')->all();
        });
    }

    /** @param array<string, string|int|bool|null> $settings */
    public static function putMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '')],
            );
        }

        Cache::forget('store-settings');
    }
}
