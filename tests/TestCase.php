<?php

namespace Tests;

use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // No test may ever reach the network. Any unfaked outbound call fails loudly.
        Http::preventStrayRequests();
    }

    /**
     * Sign in as a client user with a workspace of their own.
     *
     * Every authenticated route now runs behind ResolveWorkspace, which signs out
     * an account that has no workspace -- so a test user needs one, and the
     * Tenancy context has to be set for model scopes used outside a request.
     *
     * Idempotent: safe to call more than once within a single test.
     */
    protected function signIn(): User
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@example.test'],
            ['name' => 'Test Admin', 'password' => bcrypt('secret')],
        );

        // The default workspace the backfill migration creates -- the same one a
        // provider callback with no agent mapping falls back to. Using it here
        // keeps a signed-in test user and an incoming webhook in one tenant.
        $workspace = $user->workspaces()->first()
            ?? Workspace::where('slug', 'default')->first()
            ?? $this->makeWorkspace('Default Workspace');

        if (! $user->belongsToWorkspace($workspace)) {
            Membership::create([
                'workspace_id' => $workspace->id,
                'user_id'      => $user->id,
                'role'         => Membership::OWNER,
            ]);
        }

        $user->forceFill(['current_workspace_id' => $workspace->id])->save();

        $this->actingAs($user);
        app(Tenancy::class)->set($workspace);

        return $user->refresh();
    }

    /** A workspace with no users attached yet. */
    protected function makeWorkspace(string $name): Workspace
    {
        return Workspace::create([
            'name'     => $name,
            'slug'     => Workspace::uniqueSlug($name),
            'status'   => 'active',
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
        ]);
    }

    /**
     * A client workspace plus its owner, as a pair.
     *
     * @return array{0:Workspace,1:User}
     */
    protected function makeClient(string $name, ?string $email = null): array
    {
        $workspace = $this->makeWorkspace($name);

        $user = User::create([
            'name'                 => $name . ' Owner',
            'email'                => $email ?? str($name)->slug()->append('@example.test')->value(),
            'password'             => bcrypt('secret'),
            'current_workspace_id' => $workspace->id,
        ]);

        Membership::create([
            'workspace_id' => $workspace->id,
            'user_id'      => $user->id,
            'role'         => Membership::OWNER,
        ]);

        return [$workspace, $user->refresh()];
    }

    /**
     * Sign in as that client.
     *
     * Deliberately does NOT pin the Tenancy context: the request has to resolve
     * its own workspace through ResolveWorkspace, exactly as a real one does.
     * Pre-setting it here previously hid a live cross-tenant leak, because the
     * scope appeared to work in tests while route-model binding ran before the
     * middleware in production. Use withinWorkspace() for direct model work.
     */
    protected function actingAsClient(Workspace $workspace, User $user): static
    {
        app(Tenancy::class)->set(null);
        $this->actingAs($user);

        return $this;
    }

    /** One of our own team: reaches the internal area. */
    protected function actingAsInternalAdmin(): User
    {
        [$workspace, $user] = $this->makeClient('Internal Team', 'internal@example.test');

        $user->forceFill(['is_internal_admin' => true])->save();

        $this->actingAs($user);
        app(Tenancy::class)->set($workspace);

        return $user->refresh();
    }

    /** Run a callback with the tenant context pinned to a workspace. */
    protected function withinWorkspace(Workspace $workspace, callable $callback): mixed
    {
        return app(Tenancy::class)->actingAs($workspace, $callback);
    }
}
