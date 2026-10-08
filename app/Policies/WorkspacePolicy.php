<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Tenancy;

/**
 * Shared ownership check for every tenant-owned model.
 *
 * The global workspace scope already keeps other tenants' rows out of queries
 * and route-model binding. This is the second lock: anything that reaches a
 * model by another path -- a relation, a cached instance, an id passed into a
 * job -- is still checked before it can be read or changed.
 */
abstract class WorkspacePolicy
{
    protected function owns(User $user, mixed $model): bool
    {
        $workspaceId = app(Tenancy::class)->id();

        if (! $workspaceId || ! isset($model->workspace_id)) {
            return false;
        }

        return (int) $model->workspace_id === (int) $workspaceId
            && $user->belongsToWorkspace($workspaceId);
    }

    public function viewAny(User $user): bool
    {
        return app(Tenancy::class)->check();
    }

    public function create(User $user): bool
    {
        return app(Tenancy::class)->check();
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->owns($user, $model);
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->owns($user, $model);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->owns($user, $model);
    }
}
