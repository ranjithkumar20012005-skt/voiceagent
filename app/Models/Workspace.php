<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** One customer business. The tenant boundary for everything else. */
class Workspace extends Model
{
    protected $fillable = [
        'name', 'slug', 'business_name', 'default_language',
        'timezone', 'currency', 'status', 'credit_balance', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata'       => 'array',
            'credit_balance' => 'decimal:4',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')->withPivot('role')->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** A slug that is readable and still unique if two businesses share a name. */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $n    = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$n);
        }

        return $slug;
    }
}
