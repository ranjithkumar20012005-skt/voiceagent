<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Non-secret operator settings. Never store API keys or webhook tokens here --
 * those live in the environment only.
 */
class AppSetting extends Model
{
    protected $table = 'app_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'app_settings.all';

    /** @return array<string,string> */
    public static function all_cached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all_cached()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => is_scalar($value) ? (string) $value : json_encode($value)]);
        Cache::forget(self::CACHE_KEY);
    }

    /** @param array<string,mixed> $values */
    public static function putMany(array $values): void
    {
        foreach ($values as $k => $v) {
            static::updateOrCreate(['key' => $k], ['value' => is_scalar($v) ? (string) $v : json_encode($v)]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
