<?php

namespace App\Models\Concerns;

use App\Models\Workspace;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Confines a model to one workspace.
 *
 * Every read is filtered by the workspace in the current Tenancy context, and
 * every new row has it stamped on automatically, so a forgotten `where` cannot
 * leak another customer's data. Route-model binding goes through the same
 * builder, which is what closes the usual ID-guessing hole.
 *
 * When no workspace is set -- migrations, seeders, scheduler, tests that have
 * not pinned a tenant -- the filter is skipped rather than silently matching
 * nothing. Anything running without a request must therefore scope explicitly,
 * which is why jobs resolve a workspace and run inside Tenancy::actingAs().
 */
trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            if ($id = app(Tenancy::class)->id()) {
                $builder->where($builder->getModel()->getTable() . '.workspace_id', $id);
            }
        });

        static::creating(function ($model) {
            if ($model->workspace_id === null && $id = app(Tenancy::class)->id()) {
                $model->workspace_id = $id;
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** Escape hatch for admin/console work that must cross tenants on purpose. */
    public function scopeAcrossWorkspaces(Builder $query): Builder
    {
        return $query->withoutGlobalScope('workspace');
    }

    public function scopeForWorkspace(Builder $query, Workspace|int $workspace): Builder
    {
        return $query->withoutGlobalScope('workspace')
            ->where($this->getTable() . '.workspace_id', $workspace instanceof Workspace ? $workspace->id : $workspace);
    }
}
