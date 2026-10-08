<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Settings a customer can change about their own workspace.
 *
 * The cache key carries the workspace id. The existing app_settings cache is a
 * single global entry, so reusing it here would have served one customer's
 * values to the next -- a cross-tenant leak through the cache rather than the
 * database.
 */
class WorkspaceSetting extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['workspace_id', 'key', 'value'];

    private static function cacheKey(int $workspaceId): string
    {
        return "workspace_settings:{$workspaceId}";
    }

    /** @return array<string,string> */
    public static function allForCurrent(): array
    {
        $id = app(Tenancy::class)->id();

        if (! $id) {
            return [];
        }

        return Cache::remember(
            self::cacheKey($id),
            3600,
            fn () => static::query()->pluck('value', 'key')->all(),
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::allForCurrent()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $id = app(Tenancy::class)->id();

        if (! $id) {
            return;
        }

        static::updateOrCreate(
            ['workspace_id' => $id, 'key' => $key],
            ['value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value)],
        );

        Cache::forget(self::cacheKey($id));
    }

    /** @param array<string,mixed> $values */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::put($key, $value);
        }
    }
}
