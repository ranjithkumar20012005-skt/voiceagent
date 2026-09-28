<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Start Free" goes straight to the dashboard (development only); "Sign In"
 * stays the normal credential form.
 */
class StartFreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_start_free_link_points_at_the_direct_route(): void
    {
        $body = $this->get('/')->assertOk()->getContent();

        preg_match_all('#<a href="([^"]+)"[^>]*>\s*Start [Ff]ree#', $body, $m);

        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $href) {
            $this->assertStringEndsWith('/start', $href);
        }

        $this->assertStringContainsString(route('login') . '"', $body);
    }

    public function test_start_free_opens_the_dashboard_with_a_demo_session_when_enabled(): void
    {
        config(['app.start_free_demo' => true]);

        $this->get('/start')->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->assertSame('demo@voiceagent.local', auth()->user()->email);
        $this->get('/dashboard')->assertOk()->assertDontSee('Create Account')->assertDontSee('Workspace name');
    }

    public function test_start_free_never_opens_a_session_in_production(): void
    {
        config(['app.start_free_demo' => true]);
        $this->app['env'] = 'production';

        $this->get('/start')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'demo@voiceagent.local']);
    }

    public function test_start_free_falls_back_to_sign_in_when_disabled(): void
    {
        config(['app.start_free_demo' => false]);

        $this->get('/start')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_sign_in_is_still_the_credential_form(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('Create Account');

        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => bcrypt('secret')]);

        $this->post('/login', ['email' => 'admin@example.test', 'password' => 'secret'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}
