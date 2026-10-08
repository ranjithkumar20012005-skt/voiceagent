<?php

namespace App\Support;

use App\Models\Workspace;

/**
 * The workspace the current request (or job) is acting for.
 *
 * Resolved once per request by ResolveWorkspace middleware and read by the
 * BelongsToWorkspace scope. Jobs and console commands set it explicitly,
 * because there is no authenticated user to infer it from.
 *
 * Held as a singleton rather than read from Auth on each query so that a queued
 * job can act for a workspace without a logged-in user, and so tests can pin a
 * tenant without signing anybody in.
 */
class Tenancy
{
    private ?Workspace $workspace = null;

    public function set(?Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function workspace(): ?Workspace
    {
        return $this->workspace;
    }

    public function id(): ?int
    {
        return $this->workspace?->id;
    }

    public function check(): bool
    {
        return $this->workspace !== null;
    }

    /**
     * Run a callback as though the request belonged to another workspace, then
     * restore whatever was set before. Used by jobs and the webhook, which
     * resolve their workspace from the payload rather than from a session.
     */
    public function actingAs(?Workspace $workspace, callable $callback): mixed
    {
        $previous = $this->workspace;
        $this->workspace = $workspace;

        try {
            return $callback();
        } finally {
            $this->workspace = $previous;
        }
    }
}
