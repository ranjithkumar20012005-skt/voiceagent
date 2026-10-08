<?php

namespace App\Http\Controllers;

use App\Jobs\ProvisionAgentJob;
use App\Models\Agent;
use App\Services\Provider\AgentBlueprint;
use App\Services\Provider\AgentProvisioner;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Create and edit an agent -- open to any signed-in workspace user.
 *
 * The form speaks our vocabulary -- name, role, greeting, instructions, goal,
 * voice, language -- and AgentBlueprint turns that into the platform's config and
 * a properly structured prompt. Nothing about the provider appears in the form.
 *
 * Provisioning is dispatched immediately on save, so the agent starts being built
 * before the redirect lands -- store() sends the caller to the Agents list, where
 * that card shows the build happening instead of a static "Setting up" message.
 * Where the platform cannot be reached the agent waits for a person on our team
 * to finish it instead, which the same card shows plainly rather than pretending.
 */
class AgentBuilderController extends Controller
{
    public function __construct(private readonly Tenancy $tenancy)
    {
    }

    public function create(): View
    {
        return view('agents.build', [
            'agent'     => new Agent([
                'calling_mode'     => Agent::MODE_INSTANT_LEADS,
                'default_language' => 'English',
                'max_call_seconds' => 420,
                'temperature'      => 0.40,
                'status'           => Agent::DRAFT,
            ]),
            'editing'   => false,
            'voices'    => config('voice.voices', []),
            'languages' => config('voice.languages', []),
            'modes'     => Agent::CALLING_MODES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $agent = new Agent($data);
        $agent->workspace_id = $this->tenancy->id();
        $agent->status       = Agent::DRAFT;
        $agent->save();

        if (Agent::count() === 1) {
            $agent->markAsDefault();
        }

        // Straight onto the queue so the platform write starts now.
        ProvisionAgentJob::dispatch($agent->id);

        // Back to the list rather than straight into setup: the card there
        // shows this agent being built in real time (agent-card.blade.php
        // polls agents.card until it's ready), which is a truer answer to
        // "what happens now" than jumping to a setup form for an agent that
        // cannot place calls yet. Setup is one click away from the finished card.
        return redirect()
            ->route('agents.index')
            ->with('status', "'{$agent->name}' is being built -- watch it come together below.");
    }

    public function edit(Agent $agent): View
    {
        return view('agents.build', [
            'agent'     => $agent,
            'editing'   => true,
            'voices'    => config('voice.voices', []),
            'languages' => config('voice.languages', []),
            'modes'     => Agent::CALLING_MODES,
        ]);
    }

    public function update(Request $request, Agent $agent, AgentProvisioner $provisioner)
    {
        $agent->fill($this->validated($request))->save();

        // An edit to a live agent is pushed straight away; an edit to one that
        // never provisioned retries the create.
        $result = $provisioner->sync($agent->refresh());

        return redirect()->route('agents.show', $agent)->with(
            'status',
            $result['provisioned']
                ? "'{$agent->name}' has been updated."
                : "'{$agent->name}' has been saved. {$result['reason']}",
        );
    }

    /**
     * Retry provisioning by hand.
     *
     * This is the one-click step that finishes an agent the platform could not
     * take automatically, and the retry for one that failed.
     */
    public function provision(Agent $agent, AgentProvisioner $provisioner)
    {
        $result = $provisioner->provision($agent);

        return back()->with(
            'status',
            $result['provisioned']
                ? "'{$agent->name}' is live and ready to call."
                : "Could not finish setting up '{$agent->name}'. {$result['reason']}",
        );
    }

    /**
     * The prompt this agent's answers produce, so it can be read before saving.
     * Useful review step, and it never reaches the platform.
     */
    public function preview(Agent $agent): View
    {
        return view('agents.preview', [
            'agent'  => $agent,
            'prompt' => AgentBlueprint::for($agent)->prompt(),
            'opening' => AgentBlueprint::for($agent)->openingLine(),
            'variables' => array_keys(AgentBlueprint::for($agent)->variables()),
        ]);
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:80'],
            'role'                => ['nullable', 'string', 'max:120'],
            'description'         => ['nullable', 'string', 'max:255'],
            'calling_mode'        => ['required', Rule::in(array_keys(Agent::CALLING_MODES))],
            'default_language'    => ['required', Rule::in(config('voice.languages', []))],
            'secondary_languages' => ['nullable', 'array'],
            'secondary_languages.*' => [Rule::in(config('voice.languages', []))],
            'voice'               => ['nullable', Rule::in(array_keys(config('voice.voices', [])))],
            'first_message'       => ['nullable', 'string', 'max:300'],
            'instructions'        => ['required', 'string', 'max:6000'],
            'goal'                => ['required', 'string', 'max:300'],
            'closing_message'     => ['nullable', 'string', 'max:300'],
            'objection_handling'  => ['nullable', 'string', 'max:3000'],
            'max_call_seconds'    => ['required', 'integer', 'min:60', 'max:1800'],
            'temperature'         => ['required', 'numeric', 'min:0', 'max:1'],
            'variable_names'      => ['nullable', 'array'],
            'variable_names.*'    => ['nullable', 'string', 'max:40'],
            'variable_descriptions'   => ['nullable', 'array'],
            'variable_descriptions.*' => ['nullable', 'string', 'max:160'],
            'faq_questions'       => ['nullable', 'array'],
            'faq_questions.*'     => ['nullable', 'string', 'max:160'],
            'faq_answers'         => ['nullable', 'array'],
            'faq_answers.*'       => ['nullable', 'string', 'max:400'],
        ], [
            'instructions.required' => 'Describe what the agent should do on the call.',
            'goal.required'         => 'Say what a successful call looks like.',
        ]);

        // Paired name/description inputs become the variable bag.
        $data['business_variables'] = $this->pairs(
            $request->input('variable_names', []),
            $request->input('variable_descriptions', []),
        );

        $data['faqs'] = $this->pairs(
            $request->input('faq_questions', []),
            $request->input('faq_answers', []),
        );

        $data['secondary_languages'] = array_values(array_diff(
            (array) ($data['secondary_languages'] ?? []),
            [$data['default_language']],
        ));

        unset($data['variable_names'], $data['variable_descriptions'], $data['faq_questions'], $data['faq_answers']);

        return $data;
    }

    /**
     * Zip two parallel input arrays into one map, dropping rows with no key.
     *
     * @return array<string,string>
     */
    private function pairs(mixed $keys, mixed $values): array
    {
        $keys   = is_array($keys) ? array_values($keys) : [];
        $values = is_array($values) ? array_values($values) : [];
        $out    = [];

        foreach ($keys as $i => $key) {
            $key = trim((string) $key);

            if ($key === '') {
                continue;
            }

            $out[$key] = trim((string) ($values[$i] ?? ''));
        }

        return $out;
    }
}
