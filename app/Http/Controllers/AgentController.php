<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\SarvamVoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * My Agents / Create Agent / Edit Agent.
 *
 * An agent row points at an agent that already exists in the voice platform.
 * Nothing here creates or edits the agent upstream -- the prompt, voice and
 * knowledge are managed in the platform, and the fields stored here are the
 * team's reference copy. Calls placed with an agent use its app id/version.
 */
class AgentController extends Controller
{
    public function __construct(private readonly SarvamVoiceService $voice)
    {
    }

    public function index(): View
    {
        return view('agents.index', [
            'agents'      => Agent::withCount('callAttempts')->orderByDesc('is_default')->orderBy('name')->get(),
            'workspace'   => $this->voice->publicStatus(),
            'unassigned'  => \App\Models\CallAttempt::whereNull('agent_id')->count(),
        ]);
    }

    public function create(): View
    {
        return view('agents.form', [
            'agent'     => new Agent(['status' => Agent::ACTIVE, 'default_language' => config('sarvam.default_language')]),
            'languages' => config('sarvam.languages', []),
            'workspace' => $this->voice->publicStatus(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $agent = Agent::create($data + ['user_id' => $request->user()->id]);

        // The first agent becomes the default so "New Call" has a sensible pick.
        if ($request->boolean('is_default') || Agent::count() === 1) {
            $agent->markAsDefault();
        }

        return redirect()->route('agents.edit', $agent)->with('status', 'Agent created.');
    }

    public function edit(Agent $agent): View
    {
        $agent->loadCount('callAttempts');

        return view('agents.form', [
            'agent'     => $agent,
            'languages' => config('sarvam.languages', []),
            'workspace' => $this->voice->publicStatus(),
            'recent'    => $agent->callAttempts()->with('customer')->latest('id')->limit(8)->get(),
        ]);
    }

    public function update(Request $request, Agent $agent)
    {
        $agent->update($this->validated($request));

        if ($request->boolean('is_default') && $agent->isActive()) {
            $agent->markAsDefault();
        } elseif (! $request->boolean('is_default') && $agent->is_default) {
            $agent->forceFill(['is_default' => false])->save();
        }

        return back()->with('status', 'Agent saved.');
    }

    public function makeDefault(Agent $agent)
    {
        abort_unless($agent->isActive(), 422, 'Only an active agent can be the default.');

        $agent->markAsDefault();

        return back()->with('status', $agent->name . ' is now the default agent.');
    }

    /**
     * An agent with call history is kept for reporting and only deactivated;
     * one that never placed a call is removed outright.
     */
    public function destroy(Agent $agent)
    {
        if ($agent->callAttempts()->exists()) {
            $agent->update(['status' => Agent::INACTIVE, 'is_default' => false]);

            return redirect()->route('agents.index')
                ->with('status', $agent->name . ' has call history, so it was deactivated instead of deleted.');
        }

        $agent->delete();

        return redirect()->route('agents.index')->with('status', 'Agent deleted.');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:80'],
            'description'          => ['nullable', 'string', 'max:255'],
            'platform_app_id'      => ['nullable', 'string', 'max:191', 'regex:/^[A-Za-z0-9._\-]+$/', 'required_with:platform_app_version'],
            'platform_app_version' => ['nullable', 'integer', 'min:1', 'max:100000', 'required_with:platform_app_id'],
            'default_language'     => ['nullable', Rule::in(config('sarvam.languages', []))],
            'first_message'        => ['nullable', 'string', 'max:2000'],
            'instructions'         => ['nullable', 'string', 'max:20000'],
            'status'               => ['required', Rule::in([Agent::ACTIVE, Agent::INACTIVE])],
        ], [
            'platform_app_id.regex'             => 'The agent ID may contain only letters, numbers, dots, dashes and underscores.',
            'platform_app_id.required_with'     => 'Enter the agent ID together with its version.',
            'platform_app_version.required_with' => 'Enter the version together with the agent ID.',
        ]);

        if ($data['status'] === Agent::INACTIVE) {
            $data['is_default'] = false;
        }

        return $data;
    }
}
