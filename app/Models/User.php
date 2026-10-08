<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_workspace_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_internal_admin' => 'boolean',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'memberships')->withPivot('role')->withTimestamps();
    }

    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    /**
     * The workspace this user is acting in.
     *
     * Falls back to their first membership when current_workspace_id is unset or
     * points somewhere they are no longer a member of -- a stale pointer must not
     * become a way into another tenant.
     */
    public function resolveWorkspace(): ?Workspace
    {
        if ($this->current_workspace_id) {
            $workspace = $this->workspaces()->whereKey($this->current_workspace_id)->first();

            if ($workspace) {
                return $workspace;
            }
        }

        return $this->workspaces()->orderBy('workspaces.id')->first();
    }

    public function membershipFor(Workspace|int $workspace): ?Membership
    {
        $id = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $this->memberships()->where('workspace_id', $id)->first();
    }

    public function belongsToWorkspace(Workspace|int $workspace): bool
    {
        return $this->membershipFor($workspace) !== null;
    }

    public function switchTo(Workspace $workspace): bool
    {
        if (! $this->belongsToWorkspace($workspace)) {
            return false;
        }

        $this->forceFill(['current_workspace_id' => $workspace->id])->save();

        return true;
    }
}
