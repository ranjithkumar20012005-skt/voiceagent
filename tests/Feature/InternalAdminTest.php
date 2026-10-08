<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\CallAttempt;
use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The internal area, and the mapping it exists for.
 *
 * Our team creates each client's workspace and records which hosted agent runs
 * their calls. Two things are being proved here: a client can never reach any of
 * it, and the provider identifiers entered here never surface in a client's
 * dashboard.
 */
class InternalAdminTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET_AGENT_ID = 'Renewal-ins-2f5d65f0-ab95';

    // =================================================================
    // Access
    // =================================================================

    public function test_a_client_user_cannot_see_the_internal_area_at_all(): void
    {
        [$workspace, $client] = $this->makeClient('ABC Hospital');

        $this->actingAsClient($workspace, $client);

        // 404 rather than 403: a 403 would confirm the area exists.
        $this->get('/internal/clients')->assertNotFound();
        $this->get('/internal/clients/create')->assertNotFound();
        $this->get("/internal/clients/{$workspace->id}")->assertNotFound();
        $this->post("/internal/clients/{$workspace->id}/agent", [])->assertNotFound();
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/internal/clients')->assertRedirect('/login');
    }

    public function test_the_internal_area_is_not_advertised_in_a_clients_navigation(): void
    {
        [$workspace, $client] = $this->makeClient('ABC Hospital');

        $body = $this->actingAsClient($workspace, $client)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('/internal/clients', $body);
    }

    public function test_our_own_team_can_open_the_internal_area(): void
    {
        $this->actingAsInternalAdmin();

        $this->get('/internal/clients')->assertOk()->assertSee('Clients');
    }

    // =================================================================
    // Creating a client
    // =================================================================

    public function test_creating_a_client_builds_the_workspace_the_login_and_the_ownership(): void
    {
        $this->actingAsInternalAdmin();

        $this->post(route('internal.clients.store'), [
            'business_name' => 'ABC Hospital',
            'contact_name'  => 'Priya Menon',
            'email'         => 'priya@abchospital.test',
        ])->assertRedirect();

        $workspace = Workspace::where('business_name', 'ABC Hospital')->firstOrFail();
        $user      = User::where('email', 'priya@abchospital.test')->firstOrFail();

        $this->assertTrue($user->belongsToWorkspace($workspace));
        $this->assertSame('owner', $user->membershipFor($workspace)->role);
        $this->assertSame($workspace->id, $user->current_workspace_id);
        $this->assertFalse($user->is_internal_admin, 'A client login must never be internal.');
    }

    public function test_a_generated_password_is_shown_once_and_never_stored_readably(): void
    {
        $this->actingAsInternalAdmin();

        $this->post(route('internal.clients.store'), [
            'business_name' => 'XYZ Realty',
            'contact_name'  => 'Arjun Rao',
            'email'         => 'arjun@xyzrealty.test',
        ])->assertRedirect()->assertSessionHas('status');

        $user    = User::where('email', 'arjun@xyzrealty.test')->firstOrFail();
        $message = session('status');

        // Whatever was generated, the stored value must be a hash, not the text.
        $this->assertStringStartsWith('$2y$', $user->password);
        $this->assertStringContainsString('arjun@xyzrealty.test', $message);
        $this->assertStringContainsString('shown once', $message);
    }

    // =================================================================
    // Mapping
    // =================================================================

    public function test_mapping_records_the_hosted_agent_and_marks_it_ready(): void
    {
        [$workspace] = $this->makeClient('ABC Hospital');
        $this->actingAsInternalAdmin();

        $this->post(route('internal.clients.agent.map', $workspace), [
            'display_name'           => 'Maya',
            'description'            => 'Appointment reminders',
            'provider_agent_id'      => self::SECRET_AGENT_ID,
            'provider_agent_version' => 4,
            'provider_deployment_id' => 'dep-123',
            'calling_mode'           => Agent::MODE_INBOUND,
        ])->assertRedirect();

        $agent = Agent::withoutGlobalScope('workspace')->where('workspace_id', $workspace->id)->firstOrFail();

        $this->assertSame('Maya', $agent->name);
        $this->assertSame(self::SECRET_AGENT_ID, $agent->provider_agent_id);
        $this->assertSame(4, $agent->provider_agent_version);
        $this->assertSame('dep-123', $agent->provider_deployment_id);
        $this->assertSame(Agent::READY, $agent->status);
        $this->assertTrue($agent->isProvisioned());
        $this->assertSame('direct_admin_mapping', $agent->provider_metadata['binding_mode']);
    }

    public function test_an_unmapped_agent_is_marked_as_needing_attention_rather_than_ready(): void
    {
        [$workspace] = $this->makeClient('Unmapped Ltd');

        // An agent created without identifiers: provisioning must refuse it, so a
        // client is never shown an agent that looks ready and cannot call.
        $agent = $this->withinWorkspace($workspace, fn () => Agent::create(['name' => 'Nothing behind it', 'status' => Agent::DRAFT]));

        $provider = app(\App\Contracts\VoiceAgentProviderInterface::class);

        $this->expectException(\App\Services\SarvamException::class);
        $provider->createAgent($agent);
    }

    public function test_a_client_cannot_map_an_agent_into_another_clients_workspace(): void
    {
        [$a, $clientA] = $this->makeClient('ABC Hospital', 'a@example.test');
        [$b]           = $this->makeClient('XYZ Realty', 'b@example.test');

        $this->actingAsClient($a, $clientA);

        $this->post(route('internal.clients.agent.map', $b), [
            'display_name'           => 'Stolen',
            'provider_agent_id'      => 'nope',
            'provider_agent_version' => 1,
            'calling_mode'           => Agent::MODE_INSTANT_LEADS,
        ])->assertNotFound();

        $this->assertSame(0, Agent::withoutGlobalScope('workspace')->count());
    }

    // =================================================================
    // White-labelling
    // =================================================================

    public function test_provider_identifiers_never_reach_a_clients_dashboard(): void
    {
        [$workspace, $client] = $this->makeClient('ABC Hospital');

        $agent = $this->withinWorkspace($workspace, function () {
            $agent = Agent::create(['name' => 'Maya', 'status' => Agent::ACTIVE]);
            $agent->forceFill([
                'provider_agent_id'      => self::SECRET_AGENT_ID,
                'provider_agent_version' => 4,
                'provider_deployment_id' => 'dep-secret-999',
            ])->save();

            $customer = Customer::create(['name' => 'Meera', 'phone_number' => '+919000000001']);
            CallAttempt::create(['attempt_id' => 'att-1', 'customer_id' => $customer->id, 'agent_id' => $agent->id, 'status' => 'completed']);

            return $agent;
        });

        $this->actingAsClient($workspace, $client);

        // Every page a client can open, not a sample: this is the check that
        // keeps provider wording from creeping back into the product.
        $pages = [
            '/dashboard', '/agents', '/calls', '/leads', '/callbacks', '/analytics',
            '/usage', '/phone-numbers', '/settings', '/customers', '/campaigns',
            '/imports', '/calling', '/automations', '/knowledge-base', '/tools',
        ];

        foreach ($pages as $url) {
            $body = $this->get($url)->assertOk()->getContent();

            foreach ([self::SECRET_AGENT_ID, 'dep-secret-999', 'Sarvam', 'sarvam', 'Vobiz', 'SARVAM_API_KEY', 'app_version'] as $leak) {
                $this->assertStringNotContainsString($leak, $body, "[{$leak}] leaked on {$url}");
            }
        }

        // And not through model serialization either.
        $this->assertArrayNotHasKey('provider_agent_id', $agent->fresh()->toArray());
        $this->assertArrayNotHasKey('provider_deployment_id', $agent->fresh()->toArray());
    }

    public function test_numbers_hide_provider_details_when_serialized(): void
    {
        $number = PhoneNumber::create([
            'phone_number'       => '+914012345678',
            'country'            => 'IN',
            'status'             => PhoneNumber::AVAILABLE,
            'provider_number_id' => 'prov-num-secret',
        ]);

        $array = $number->toArray();

        $this->assertArrayNotHasKey('provider_number_id', $array);
        $this->assertArrayNotHasKey('provider', $array);
        $this->assertSame('+914012345678', $array['phone_number']);
    }

    // =================================================================
    // Numbers
    // =================================================================

    public function test_numbers_are_imported_normalised_and_deduplicated(): void
    {
        $this->actingAsInternalAdmin();

        $this->post(route('internal.numbers.import'), [
            'numbers' => "9876543210\n+919876543210\n08012345678",
            'country' => 'IN',
        ])->assertRedirect();

        // The first two normalise to the same E.164 number, so one is skipped.
        $this->assertSame(2, PhoneNumber::count());
        $this->assertTrue(PhoneNumber::where('phone_number', '+919876543210')->exists());
    }

    public function test_assigning_a_number_attaches_it_to_the_clients_agent(): void
    {
        [$workspace] = $this->makeClient('ABC Hospital');

        $agent = $this->withinWorkspace($workspace, fn () => Agent::create(['name' => 'Maya', 'status' => Agent::ACTIVE]));

        $number = PhoneNumber::create(['phone_number' => '+914012345678', 'country' => 'IN', 'status' => PhoneNumber::AVAILABLE]);

        $this->actingAsInternalAdmin();

        $this->post(route('internal.clients.numbers.assign', $workspace), [
            'phone_number_id' => $number->id,
            'agent_id'        => $agent->id,
        ])->assertRedirect();

        $number->refresh();

        $this->assertSame($workspace->id, $number->workspace_id);
        $this->assertSame($agent->id, $number->assigned_agent_id);
        $this->assertSame(PhoneNumber::ACTIVE, $number->status);
        $this->assertSame($number->id, Agent::withoutGlobalScope('workspace')->find($agent->id)->assigned_phone_number_id);
    }

    public function test_a_number_already_held_by_another_client_is_refused(): void
    {
        [$a] = $this->makeClient('ABC Hospital', 'a@example.test');
        [$b] = $this->makeClient('XYZ Realty', 'b@example.test');

        $agentB = $this->withinWorkspace($b, fn () => Agent::create(['name' => 'Arjun', 'status' => Agent::ACTIVE]));

        $number = PhoneNumber::create(['phone_number' => '+914012345678', 'country' => 'IN', 'status' => PhoneNumber::AVAILABLE]);
        app(\App\Services\PhoneNumberAllocator::class)->claim($number, $a);

        $this->actingAsInternalAdmin();

        $this->post(route('internal.clients.numbers.assign', $b), [
            'phone_number_id' => $number->id,
            'agent_id'        => $agentB->id,
        ])->assertSessionHasErrors('phone_number_id');

        $this->assertSame($a->id, $number->fresh()->workspace_id);
    }
}
