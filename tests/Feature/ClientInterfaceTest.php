<?php

namespace Tests\Feature;

use App\Models\CallAttempt;
use App\Models\Customer;
use App\Support\CallStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The simplified client interface: what it shows, and -- just as important --
 * what it must never show.
 */
class ClientInterfaceTest extends TestCase
{
    use RefreshDatabase;

    private function hotLead(): CallAttempt
    {
        $customer = Customer::create([
            'name'          => 'Ramesh Rao',
            'phone_number'  => '9876543210',
            'policy_number' => 'POL-2291',
        ]);

        return CallAttempt::create([
            'customer_id'           => $customer->id,
            'attempt_id'            => 'att-hot-1',
            'direction'             => 'outbound',
            'status'                => CallStatus::COMPLETED,
            'connectivity_status'   => CallStatus::CONNECTED,
            'call_disposition'      => CallStatus::INTERESTED,
            'lead_generated'        => true,
            'duration_seconds'      => 198,
            'customer_phone_number' => '+919876543210',
            'started_at'            => now()->subMinutes(5),
            'ended_at'              => now(),
            'transcript'            => [
                ['role' => 'agent', 'en_text' => 'Hello, am I speaking with Ramesh?'],
                ['role' => 'user', 'en_text' => 'Yes, speaking.'],
            ],
            'output_agent_variables' => ['call_disposition' => 'interested'],
        ]);
    }

    // =================================================================
    // Public marketing site
    // =================================================================

    public function test_the_landing_page_is_public(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('AI Voice Agents That Talk')
            ->assertSee('How It Works');
    }

    public function test_the_landing_page_falls_back_to_contact_sales_without_configured_prices(): void
    {
        config(['pricing.plans' => [[
            'name' => 'Starter', 'tagline' => 'Small volumes',
            'price' => null, 'period' => null, 'featured' => false, 'features' => ['A feature'],
        ]]]);

        $this->get('/')->assertOk()->assertSee('Contact Sales');
    }

    // =================================================================
    // Overview
    // =================================================================

    public function test_the_overview_shows_hot_leads_and_the_five_kpis(): void
    {
        $this->signIn();
        $this->hotLead();

        $response = $this->get('/dashboard')->assertOk();

        $response->assertSee('Overview')
            ->assertSee('Monitor AI calling activity and customer outcomes.')
            ->assertSee('Calls Today')
            ->assertSee('Connected')
            ->assertSee('Hot Leads')
            ->assertSee('Follow-ups')
            ->assertSee('Total Minutes')
            ->assertSee('Ramesh Rao')
            ->assertSee('View Call');
    }

    public function test_the_overview_says_so_when_there_is_nothing_to_show(): void
    {
        $this->signIn();

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('No hot leads yet', false)
            ->assertSee('No calls yet', false);
    }

    // =================================================================
    // Calling and leads
    // =================================================================

    public function test_the_calling_page_offers_instant_call_and_defers_the_rest(): void
    {
        $this->signIn();

        $this->get('/calling')
            ->assertOk()
            ->assertSee('Instant AI Call')
            ->assertSee('Bulk Calling')
            ->assertSee('Coming Soon', false)
            ->assertSee('Start Call');
    }

    public function test_the_leads_page_lists_hot_leads(): void
    {
        $this->signIn();
        $this->hotLead();

        $this->get('/leads')->assertOk()->assertSee('Ramesh Rao')->assertSee('Interested');
    }

    public function test_the_client_pages_require_authentication(): void
    {
        $this->get('/calling')->assertRedirect('/login');
        $this->get('/leads')->assertRedirect('/login');
    }

    // =================================================================
    // Call detail
    // =================================================================

    public function test_the_call_detail_shows_the_transcript_in_business_wording(): void
    {
        $this->signIn();
        $call = $this->hotLead();

        // The panel is headed "Conversation" since the result page was rebuilt;
        // what matters is that the turns render in business wording.
        $this->get('/calls/' . $call->id)
            ->assertOk()
            ->assertSee('Conversation')
            ->assertSee('Hello, am I speaking with Ramesh?')
            ->assertSee('Yes, speaking.')
            ->assertSee('Answered')
            ->assertSee('Interested');
    }

    public function test_the_call_detail_hides_internal_identifiers_from_clients(): void
    {
        config(['app.debug' => false]);

        $this->signIn();
        $call = $this->hotLead();

        $this->get('/calls/' . $call->id)
            ->assertOk()
            ->assertDontSee('att-hot-1')
            ->assertDontSee('Internal Detail');
    }

    // =================================================================
    // White labelling
    // =================================================================

    /**
     * The vendor name and every credential-adjacent identifier must be absent
     * from the rendered client interface.
     */
    public function test_no_page_leaks_the_voice_platform_or_its_configuration(): void
    {
        config(['app.debug' => false]);

        $this->signIn();
        $call = $this->hotLead();

        $secrets = array_filter([
            config('sarvam.api_key'),
            config('sarvam.org_id'),
            config('sarvam.workspace_id'),
            config('sarvam.app_id'),
            config('sarvam.connection_id'),
            config('sarvam.webhook_token'),
            config('sarvam.base_url'),
        ]);

        foreach (['/', '/login', '/dashboard', '/calling', '/leads', '/calls', '/calls/' . $call->id] as $url) {
            $body = $this->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsStringIgnoringCase('sarvam', $body, "Vendor name leaked on {$url}");

            foreach ($secrets as $secret) {
                $this->assertStringNotContainsString((string) $secret, $body, "A configured value leaked on {$url}");
            }
        }
    }
}
