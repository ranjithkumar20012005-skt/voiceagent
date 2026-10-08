<?php

namespace Tests\Feature;

use App\Jobs\ProvisionAgentJob;
use App\Models\Agent;
use App\Services\Provider\AgentBlueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Creating an agent from our own dashboard.
 *
 * Two things are proved: the form produces a provider-ready config and a properly
 * structured prompt without anyone writing provider JSON, and an agent that has
 * not finished provisioning never pretends it is ready.
 */
class AgentBuilderTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string,mixed> */
    private function formData(array $overrides = []): array
    {
        return array_merge([
            'name'                  => 'Maya',
            'role'                  => 'appointment assistant',
            'description'           => 'Books appointments for the cardiology department',
            'calling_mode'          => Agent::MODE_INSTANT_LEADS,
            'default_language'      => 'Hindi',
            'secondary_languages'   => ['English', 'Telugu'],
            'voice'                 => 'kavya',
            'goal'                  => 'book or confirm an appointment',
            'instructions'          => "Ask which department they need.\nOffer the next available slots and confirm one.\nIf they are busy, ask when to call back.",
            'objection_handling'    => "Never give medical advice.\nNever quote prices.",
            'closing_message'       => 'Thank you for your time.',
            'max_call_seconds'      => 420,
            'temperature'           => '0.40',
            'variable_names'        => ['policy_number', ''],
            'variable_descriptions' => ['The policy being discussed', ''],
            'faq_questions'         => ['opening hours'],
            'faq_answers'           => ['Nine in the morning to seven in the evening'],
        ], $overrides);
    }

    // =================================================================
    // Creating
    // =================================================================

    public function test_an_agent_is_created_from_the_form_and_queued_for_provisioning(): void
    {
        Queue::fake();
        $this->actingAsInternalAdmin();

        $this->post(route('agents.store'), $this->formData())->assertRedirect();

        $agent = Agent::withoutGlobalScope('workspace')->where('name', 'Maya')->firstOrFail();

        $this->assertSame('appointment assistant', $agent->role);
        $this->assertSame('Hindi', $agent->default_language);
        $this->assertSame(['English', 'Telugu'], $agent->secondary_languages);
        $this->assertSame('kavya', $agent->voice);
        $this->assertSame(420, $agent->max_call_seconds);
        $this->assertSame(0.4, $agent->temperature);

        // Paired inputs became maps, and the blank row was dropped.
        $this->assertSame(['policy_number' => 'The policy being discussed'], $agent->business_variables);
        $this->assertSame(['opening hours' => 'Nine in the morning to seven in the evening'], $agent->faqs);

        // Provisioning starts immediately rather than on a later sweep.
        Queue::assertPushed(ProvisionAgentJob::class, fn ($job) => $job->agentId === $agent->id);
    }

    public function test_the_primary_language_is_not_duplicated_into_the_additional_ones(): void
    {
        Queue::fake();
        $this->actingAsInternalAdmin();

        $this->post(route('agents.store'), $this->formData([
            'default_language'    => 'Hindi',
            'secondary_languages' => ['Hindi', 'English'],
        ]))->assertRedirect();

        $agent = Agent::withoutGlobalScope('workspace')->where('name', 'Maya')->firstOrFail();

        $this->assertSame(['English'], $agent->secondary_languages);
    }

    public function test_the_form_refuses_an_agent_with_no_instructions_or_goal(): void
    {
        Queue::fake();
        $this->actingAsInternalAdmin();

        $this->post(route('agents.store'), $this->formData(['instructions' => '', 'goal' => '']))
            ->assertSessionHasErrors(['instructions', 'goal']);

        $this->assertSame(0, Agent::withoutGlobalScope('workspace')->count());
        Queue::assertNothingPushed();
    }

    public function test_an_unknown_voice_or_language_is_refused(): void
    {
        Queue::fake();
        $this->actingAsInternalAdmin();

        $this->post(route('agents.store'), $this->formData(['voice' => 'not-a-voice']))
            ->assertSessionHasErrors('voice');

        $this->post(route('agents.store'), $this->formData(['default_language' => 'Klingon']))
            ->assertSessionHasErrors('default_language');
    }

    public function test_a_created_agent_belongs_to_the_acting_workspace(): void
    {
        Queue::fake();
        $admin = $this->actingAsInternalAdmin();

        $this->post(route('agents.store'), $this->formData())->assertRedirect();

        $agent = Agent::withoutGlobalScope('workspace')->where('name', 'Maya')->firstOrFail();

        $this->assertSame($admin->current_workspace_id, $agent->workspace_id);
    }

    // =================================================================
    // The blueprint
    // =================================================================

    public function test_the_blueprint_builds_a_prompt_with_every_required_section(): void
    {
        Queue::fake();
        $this->actingAsInternalAdmin();
        $this->post(route('agents.store'), $this->formData());

        $agent  = Agent::withoutGlobalScope('workspace')->where('name', 'Maya')->firstOrFail();
        $prompt = AgentBlueprint::for($agent->load('workspace'))->prompt();

        foreach (['## Persona', '## Environment & Situation', '## Objective',
                  '## Speaking style rules', '## Facts', '## Conversation guidelines',
                  '## Guardrails'] as $section) {
            $this->assertStringContainsString($section, $prompt, "Missing {$section}");
        }

        // The customer's own instructions and limits made it in.
        $this->assertStringContainsString('Ask which department they need', $prompt);
        $this->assertStringContainsString('Never give medical advice', $prompt);
        $this->assertStringContainsString('opening hours', $prompt);

        // And the standard protections are added whether or not they were asked for.
        $this->assertStringContainsString('wrong-person path', $prompt);
        $this->assertStringContainsString('never ends the call', $prompt);
        $this->assertStringContainsString('Safety and escalation override', $prompt);
        $this->assertStringContainsString('{{user_name}}', $prompt);
    }

    public function test_the_blueprint_produces_the_providers_config_shape(): void
    {
        Queue::fake();
        $this->actingAsInternalAdmin();
        $this->post(route('agents.store'), $this->formData());

        $agent  = Agent::withoutGlobalScope('workspace')->where('name', 'Maya')->firstOrFail();
        $config = AgentBlueprint::for($agent->load('workspace'))->config();

        $this->assertSame(0.4, $config['llm_config']['temperature']);
        $this->assertSame('Hindi', $config['language_config']['initial_language_name']);
        $this->assertSame(420, $config['interaction_config']['max_interaction_time_seconds']);
        $this->assertArrayHasKey('audio', $config['intro_message_config']);

        $variables = $config['llm_config']['agent_config']['agent_variables'];

        $this->assertArrayHasKey('user_name', $variables);
        $this->assertArrayHasKey('business_name', $variables);
        $this->assertArrayHasKey('policy_number', $variables);

        // The two post-call variables are what make an outcome and a summary
        // appear on the result page without us generating either.
        $this->assertTrue($variables['call_outcome']['update_post_interaction']);
        $this->assertTrue($variables['call_summary']['update_post_interaction']);
    }

    public function test_an_opening_line_is_written_when_none_was_given(): void
    {
        Queue::fake();
        $this->actingAsInternalAdmin();
        $this->post(route('agents.store'), $this->formData(['first_message' => '']));

        $agent   = Agent::withoutGlobalScope('workspace')->where('name', 'Maya')->firstOrFail();
        $opening = AgentBlueprint::for($agent->load('workspace'))->openingLine();

        $this->assertStringContainsString('Maya', $opening);
        $this->assertLessThanOrEqual(25, str_word_count($opening), 'The opening line stays under 25 words.');
    }

    // =================================================================
    // Honest status
    // =================================================================

    public function test_an_agent_that_has_not_provisioned_is_not_callable_and_says_so(): void
    {
        Queue::fake();
        $admin = $this->actingAsInternalAdmin();
        $this->post(route('agents.store'), $this->formData());

        $agent = Agent::withoutGlobalScope('workspace')->where('name', 'Maya')->firstOrFail();

        // No provider identifiers yet, so it must refuse to be treated as live.
        $this->assertFalse($agent->isProvisioned());
        $this->assertFalse($agent->canPlaceCalls());

        $this->get(route('agents.show', $agent))
            ->assertOk()
            ->assertSee('Setting up');
    }

    public function test_the_prompt_preview_is_readable_before_going_live(): void
    {
        Queue::fake();
        $this->actingAsInternalAdmin();
        $this->post(route('agents.store'), $this->formData());

        $agent = Agent::withoutGlobalScope('workspace')->where('name', 'Maya')->firstOrFail();

        $this->get(route('agents.preview', $agent))
            ->assertOk()
            ->assertSee('## Persona', false)
            ->assertSee('call_outcome');
    }

    public function test_editing_an_agent_updates_it_without_losing_its_mapping(): void
    {
        Queue::fake();
        $this->actingAsInternalAdmin();
        $this->post(route('agents.store'), $this->formData());

        $agent = Agent::withoutGlobalScope('workspace')->where('name', 'Maya')->firstOrFail();
        $agent->forceFill([
            'provider_agent_id'      => 'Live-agent-0001',
            'provider_agent_version' => 2,
            'status'                 => Agent::READY,
        ])->save();

        $this->put(route('agents.update', $agent), $this->formData([
            'name' => 'Maya',
            'goal' => 'confirm the appointment and capture a callback',
        ]))->assertRedirect();

        $agent->refresh();

        $this->assertSame('confirm the appointment and capture a callback', $agent->goal);
        // The mapping survives an edit; losing it would silently stop the agent.
        $this->assertSame('Live-agent-0001', $agent->provider_agent_id);
        $this->assertSame(2, $agent->provider_agent_version);
    }
}
