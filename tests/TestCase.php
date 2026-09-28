<?php

namespace Tests;

use App\Models\User;
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

    /** Idempotent: safe to call more than once within a single test. */
    protected function signIn(): User
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@example.test'],
            ['name' => 'Test Admin', 'password' => bcrypt('secret')],
        );

        $this->actingAs($user);

        return $user;
    }
}
