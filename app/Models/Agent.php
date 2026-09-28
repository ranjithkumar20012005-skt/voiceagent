<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A voice agent the team can place calls with.
 *
 * Each row points at an agent that already exists in the voice platform. When
 * `platform_app_id` is blank the agent falls back to the workspace agent from
 * the environment, so the original single-agent call path keeps working.
 */
class Agent extends Model
{
    use HasFactory;

    public const ACTIVE   = 'active';
    public const INACTIVE = 'inactive';

    protected $fillable = [
        'name', 'description', 'platform_app_id', 'platform_app_version',
        'default_language', 'first_message', 'instructions', 'status',
        'is_default', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_default'           => 'boolean',
            'platform_app_version' => 'integer',
        ];
    }

    public function callAttempts(): HasMany
    {
        return $this->hasMany(CallAttempt::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', self::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    /** True when this agent overrides the workspace agent from the environment. */
    public function hasOwnPlatformApp(): bool
    {
        return filled($this->platform_app_id) && $this->platform_app_version !== null;
    }

    /**
     * The app override handed to the voice service, or null to use the
     * workspace agent from the environment.
     *
     * @return array{app_id:string,app_version:int}|null
     */
    public function platformApp(): ?array
    {
        return $this->hasOwnPlatformApp()
            ? ['app_id' => (string) $this->platform_app_id, 'app_version' => (int) $this->platform_app_version]
            : null;
    }

    /** Make this agent the default, clearing the flag everywhere else. */
    public function markAsDefault(): void
    {
        static::whereKeyNot($this->id)->update(['is_default' => false]);
        $this->forceFill(['is_default' => true])->save();
    }
}
