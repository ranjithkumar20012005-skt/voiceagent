<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\CallAttempt;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\PhoneNumber;
use App\Models\Workspace;
use App\Models\WorkspaceSetting;
use App\Services\NumberUnavailableException;
use App\Services\PhoneNumberAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * One client must never reach another client's data.
 *
 * These are the tests that matter most in this application: every business
 * record belongs to a workspace, and the isolation is enforced on the server by
 * a model scope plus policies. A regression here is a data breach, not a bug, so
 * each route that takes an id is checked by actually asking for the other
 * tenant's row.
 */
class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:Workspace,1:\App\Models\User,2:Workspace,3:\App\Models\User} */
    private function twoClients(): array
    {
        [$a, $userA] = $this->makeClient('ABC Hospital', 'a@example.test');
        [$b, $userB] = $this->makeClient('XYZ Realty', 'b@example.test');

        return [$a, $userA, $b, $userB];
    }

    // =================================================================
    // Reads
    // =================================================================

    public function test_a_client_only_sees_their_own_records(): void
    {
        [$a, $userA, $b] = $this->twoClients();

        $this->withinWorkspace($a, fn () => Customer::create(['name' => 'Anita A', 'phone_number' => '+919000000001']));
        $this->withinWorkspace($b, fn () => Customer::create(['name' => 'Bala B', 'phone_number' => '+919000000002']));

        $this->actingAsClient($a, $userA);

        $this->get('/customers')->assertOk()->assertSee('Anita A')->assertDontSee('Bala B');

        $this->assertSame(1, $this->withinWorkspace($a, fn () => Customer::count()));
        $this->assertSame('Anita A', $this->withinWorkspace($a, fn () => Customer::first()->name));
    }

    public function test_a_client_cannot_open_another_clients_records_by_id(): void
    {
        [$a, $userA, $b] = $this->twoClients();

        // Everything the other tenant owns, created as them.
        [$customer, $call, $campaign, $batch] = $this->withinWorkspace($b, function () {
            $customer = Customer::create(['name' => 'Bala B', 'phone_number' => '+919000000002']);
            $call     = CallAttempt::create(['attempt_id' => 'att-b', 'customer_id' => $customer->id, 'status' => 'completed']);
            $campaign = Campaign::create(['name' => 'B run', 'status' => 'active']);
            $batch    = ImportBatch::create(['original_filename' => 'b.csv', 'status' => ImportBatch::COMPLETED]);

            return [$customer, $call, $campaign, $batch];
        });

        $this->actingAsClient($a, $userA);

        // Route-model binding goes through the scoped builder, so the row is not
        // merely forbidden -- as far as this tenant is concerned it does not exist.
        $this->get("/customers/{$customer->id}")->assertNotFound();
        $this->get("/calls/{$call->id}")->assertNotFound();
        $this->get("/campaigns/{$campaign->id}")->assertNotFound();
        $this->get("/imports/{$batch->id}")->assertNotFound();
    }

    /**
     * Route-model binding must happen after the workspace is resolved.
     *
     * This is a regression test for a real leak: SubstituteBindings runs in the
     * web group, which by default is before route middleware, so a binding was
     * resolved while no workspace was in context and the scope had nothing to
     * filter on. A client could then open another client's record by id. The fix
     * is the middleware priority entry in bootstrap/app.php, and this test fails
     * if that entry is removed.
     *
     * It deliberately does not pin the tenant: the request must resolve its own.
     */
    public function test_route_model_binding_is_scoped_without_the_test_pinning_a_tenant(): void
    {
        [$a, $userA, $b] = $this->twoClients();

        $theirs = $this->withinWorkspace($b, function () {
            $customer = Customer::create(['name' => 'Bala B', 'phone_number' => '+919000000002']);

            return CallAttempt::create([
                'attempt_id'  => 'att-theirs',
                'customer_id' => $customer->id,
                'status'      => 'completed',
                'transcript'  => [['role' => 'agent', 'en_text' => 'Private conversation']],
            ]);
        });

        $this->actingAsClient($a, $userA);

        foreach (["/calls/{$theirs->id}", "/conversations/{$theirs->id}"] as $url) {
            $this->get($url)
                ->assertNotFound()
                ->assertDontSee('Private conversation')
                ->assertDontSee('Bala B');
        }
    }

    public function test_dashboard_and_analytics_totals_are_per_client(): void
    {
        [$a, $userA, $b] = $this->twoClients();

        $this->withinWorkspace($a, function () {
            $customer = Customer::create(['name' => 'Anita A', 'phone_number' => '+919000000001']);
            CallAttempt::create(['attempt_id' => 'att-a1', 'customer_id' => $customer->id, 'status' => 'completed', 'duration_seconds' => 60]);
        });

        $this->withinWorkspace($b, function () {
            $customer = Customer::create(['name' => 'Bala B', 'phone_number' => '+919000000002']);

            foreach (range(1, 5) as $i) {
                CallAttempt::create(['attempt_id' => "att-b{$i}", 'customer_id' => $customer->id, 'status' => 'completed', 'duration_seconds' => 60]);
            }
        });

        $this->actingAsClient($a, $userA);

        // Through the real request path, so the workspace is resolved by the
        // middleware rather than pinned by the test.
        $this->get('/dashboard')->assertOk()->assertDontSee('Bala B');
        $this->get('/analytics')->assertOk();

        $this->assertSame(1, $this->withinWorkspace($a, fn () => CallAttempt::count()), 'Client A must only count their own call.');
        $this->assertSame(5, $this->withinWorkspace($b, fn () => CallAttempt::count()));
    }

    // =================================================================
    // Writes
    // =================================================================

    public function test_a_new_record_is_stamped_with_the_acting_workspace(): void
    {
        [$a] = $this->twoClients();

        $customer = $this->withinWorkspace($a, fn () => Customer::create([
            'name'         => 'Stamped',
            'phone_number' => '+919000000009',
        ]));

        $this->assertSame($a->id, $customer->workspace_id);
    }

    public function test_making_an_agent_default_does_not_touch_another_client(): void
    {
        [$a, $userA, $b] = $this->twoClients();

        $agentB = $this->withinWorkspace($b, function () {
            $agent = Agent::create(['name' => 'B primary', 'status' => Agent::ACTIVE]);
            $agent->markAsDefault();

            return $agent;
        });

        $agentA = $this->withinWorkspace($a, function () {
            $agent = Agent::create(['name' => 'A primary', 'status' => Agent::ACTIVE]);
            $agent->markAsDefault();

            return $agent;
        });

        // The previous implementation cleared is_default across every row in the
        // table, which reached into other tenants.
        $this->assertTrue(Agent::withoutGlobalScope('workspace')->find($agentA->id)->is_default);
        $this->assertTrue(Agent::withoutGlobalScope('workspace')->find($agentB->id)->is_default);
    }

    public function test_agent_references_restart_per_client(): void
    {
        [$a, $_, $b] = $this->twoClients();

        $first  = $this->withinWorkspace($a, fn () => Agent::create(['name' => 'A one', 'status' => Agent::ACTIVE]));
        $second = $this->withinWorkspace($a, fn () => Agent::create(['name' => 'A two', 'status' => Agent::ACTIVE]));
        $other  = $this->withinWorkspace($b, fn () => Agent::create(['name' => 'B one', 'status' => Agent::ACTIVE]));

        $this->assertSame('agent_01', $first->fresh()->agent_ref);
        $this->assertSame('agent_02', $second->fresh()->agent_ref);
        $this->assertSame('agent_01', $other->fresh()->agent_ref, 'Each client counts from one.');
    }

    // =================================================================
    // Settings and cache
    // =================================================================

    public function test_settings_are_not_shared_between_clients(): void
    {
        [$a, $_userA, $b] = $this->twoClients();

        $this->withinWorkspace($a, fn () => WorkspaceSetting::put('default_language', 'Telugu'));
        $this->withinWorkspace($b, fn () => WorkspaceSetting::put('default_language', 'Hindi'));

        $this->assertSame('Telugu', $this->withinWorkspace($a, fn () => WorkspaceSetting::get('default_language')));
        $this->assertSame('Hindi', $this->withinWorkspace($b, fn () => WorkspaceSetting::get('default_language')));
    }

    public function test_the_settings_cache_is_keyed_per_client(): void
    {
        [$a, $_userA, $b] = $this->twoClients();

        $this->withinWorkspace($a, fn () => WorkspaceSetting::put('retry_limit', '5'));

        // Warm A's cache, then read B's. A shared cache key would hand B A's value.
        $this->withinWorkspace($a, fn () => WorkspaceSetting::get('retry_limit'));

        $this->assertNull($this->withinWorkspace($b, fn () => WorkspaceSetting::get('retry_limit')));
        $this->assertTrue(Cache::has("workspace_settings:{$a->id}"));
    }

    // =================================================================
    // Phone numbers
    // =================================================================

    public function test_a_number_cannot_be_claimed_by_two_clients(): void
    {
        [$a, $_userA, $b] = $this->twoClients();

        $number = PhoneNumber::create([
            'phone_number' => '+914012345678',
            'country'      => 'IN',
            'status'       => PhoneNumber::AVAILABLE,
        ]);

        $allocator = app(PhoneNumberAllocator::class);

        $claimed = $allocator->claim($number, $a);
        $this->assertSame($a->id, $claimed->workspace_id);

        // The second client must be refused, not silently take it over.
        $this->expectException(NumberUnavailableException::class);
        $allocator->claim($number->fresh(), $b);
    }

    public function test_releasing_a_number_returns_it_to_the_pool(): void
    {
        [$a] = $this->twoClients();

        $number = PhoneNumber::create(['phone_number' => '+914000000000', 'country' => 'IN', 'status' => PhoneNumber::AVAILABLE]);

        $allocator = app(PhoneNumberAllocator::class);
        $allocator->claim($number, $a);
        $released = $allocator->release($number->fresh());

        $this->assertNull($released->workspace_id);
        $this->assertSame(PhoneNumber::AVAILABLE, $released->status);
        $this->assertSame(1, PhoneNumber::inPool()->count());
    }

    // =================================================================
    // Account integrity
    // =================================================================

    public function test_a_user_cannot_switch_into_a_workspace_they_do_not_belong_to(): void
    {
        [$a, $userA, $b] = $this->twoClients();

        $this->assertFalse($userA->switchTo($b));
        $this->assertSame($a->id, $userA->fresh()->current_workspace_id);
    }

    public function test_a_stale_current_workspace_pointer_falls_back_to_a_real_membership(): void
    {
        [$a, $userA] = $this->twoClients();

        // Points at a workspace they were never a member of.
        $userA->forceFill(['current_workspace_id' => 99999])->save();

        $this->assertSame($a->id, $userA->fresh()->resolveWorkspace()?->id);
    }

    public function test_an_account_with_no_workspace_is_signed_out_rather_than_shown_everything(): void
    {
        $orphan = \App\Models\User::create([
            'name'     => 'Orphan',
            'email'    => 'orphan@example.test',
            'password' => bcrypt('secret'),
        ]);

        // With no workspace in context the model scopes stop filtering, so the
        // only safe response is to refuse the session.
        $this->actingAs($orphan)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_a_suspended_client_cannot_sign_in(): void
    {
        [$a, $userA] = $this->twoClients();

        $a->forceFill(['status' => 'suspended'])->save();

        $this->actingAs($userA)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }
}
