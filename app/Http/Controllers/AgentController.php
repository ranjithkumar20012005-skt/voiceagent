<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\CallAttempt;
use App\Models\Callback;
use App\Models\Campaign;
use App\Services\SarvamVoiceService;
use App\Support\CallStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * My Agents -- list, detail, and the live card used while one is still
 * being built.
 *
 * Building and editing an agent is AgentBuilderController's job; this
 * controller only ever reads. The create/edit/destroy actions below are
 * leftover from before that controller existed -- no longer routed, kept
 * only so the history of this file stays readable, and nothing can reach them.
 *
 * Results are scoped to the caller's workspace by the model scope, so one client
 * never sees another's agents.
 */
class AgentController extends Controller
{
    public function __construct(private readonly SarvamVoiceService $voice)
    {
    }

    public function index(): View
    {
        $agents = $this->withFigures()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->each($this->resolveLastActivity(...));

        return view('agents.index', ['agents' => $agents]);
    }

    /**
     * One agent's card, rendered standalone.
     *
     * Polled by the Agents page while a card is still building or sitting
     * with the team, so it can swap itself for whatever this returns without
     * a full page reload. Scoped by the same workspace binding as every other
     * agent route -- polling another workspace's agent is a 404, not a peek.
     */
    public function cardFragment(Agent $agent): \Illuminate\Contracts\View\View
    {
        $agent = $this->withFigures()->whereKey($agent->id)->firstOrFail();

        $this->resolveLastActivity($agent);

        return view('components.agent-card', ['agent' => $agent]);
    }

    /** The aggregates every agent card is built from, today's and historical. */
    private function withFigures(): \Illuminate\Database\Eloquent\Builder
    {
        return Agent::query()
            ->with('phoneNumber')
            ->withCount([
                'callAttempts',
                'callAttempts as calls_today_count' => fn ($q) => $q->whereDate('created_at', today()),
                'callAttempts as calls_month_count' => fn ($q) => $q->where('created_at', '>=', now()->startOfMonth()),
                'callAttempts as answered_count' => fn ($q) => $q->where('connectivity_status', CallStatus::CONNECTED),
                'callAttempts as interested_count' => fn ($q) => $q->where(fn ($w) => $w->where('lead_generated', true)
                    ->orWhere('call_disposition', CallStatus::INTERESTED)),
            ])
            ->withMax('callAttempts as last_activity_at', 'created_at');
    }

    /** withMax() hands back a raw string; every view here wants a date. */
    private function resolveLastActivity(Agent $agent): void
    {
        $agent->last_activity_at = $agent->last_activity_at
            ? \Illuminate\Support\Carbon::parse($agent->last_activity_at)
            : null;
    }

    /**
     * One agent and its whole history.
     *
     * Route-model binding goes through the workspace scope, so asking for another
     * client's agent is a 404 rather than a forbidden page. Every figure below is
     * an aggregate of this agent's own stored calls; where there is no
     * denominator the value is null and the view prints a dash.
     */
    public function show(Agent $agent): View
    {
        $agent->load('phoneNumber');

        $calls = fn () => CallAttempt::where('agent_id', $agent->id);

        $row = $calls()->selectRaw(<<<'SQL'
            COUNT(*) AS total,
            SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS answered,
            SUM(CASE WHEN connectivity_status IN ('no_answer', 'busy') THEN 1 ELSE 0 END) AS no_answer,
            SUM(CASE WHEN connectivity_status = 'failed' OR status = 'failed' THEN 1 ELSE 0 END) AS failed,
            SUM(CASE WHEN lead_generated = 1 OR call_disposition = 'interested' THEN 1 ELSE 0 END) AS interested,
            SUM(CASE WHEN call_disposition = 'not_interested' THEN 1 ELSE 0 END) AS not_interested,
            SUM(COALESCE(duration_seconds, 0)) AS talk_time,
            SUM(CASE WHEN duration_seconds IS NOT NULL AND duration_seconds > 0 THEN 1 ELSE 0 END) AS with_duration
        SQL)->first();

        $total        = (int) ($row->total ?? 0);
        $answered     = (int) ($row->answered ?? 0);
        $interested   = (int) ($row->interested ?? 0);
        $talkTime     = (int) ($row->talk_time ?? 0);
        $withDuration = (int) ($row->with_duration ?? 0);
        $avgSeconds   = $withDuration > 0 ? (int) round($talkTime / $withDuration) : null;

        return view('agents.show', [
            'agent' => $agent,
            'stats' => [
                'today'          => $calls()->whereDate('created_at', today())->count(),
                'month'          => $calls()->where('created_at', '>=', now()->startOfMonth())->count(),
                'total'          => $total,
                'answered'       => $answered,
                'no_answer'      => (int) ($row->no_answer ?? 0),
                'failed'         => (int) ($row->failed ?? 0),
                'interested'     => $interested,
                'not_interested' => (int) ($row->not_interested ?? 0),
                'minutes'        => (int) ceil($talkTime / 60),
                'avg_duration'   => $avgSeconds !== null
                    ? ($avgSeconds < 60 ? "{$avgSeconds}s" : intdiv($avgSeconds, 60) . 'm ' . ($avgSeconds % 60) . 's')
                    : null,
                // Interested out of answered: an unanswered call cannot convert.
                'conversion'     => $answered > 0 ? round($interested / $answered * 100, 1) : null,
                'conversations'  => $calls()->whereNotNull('transcript')->count(),
                'callbacks'      => Callback::where('agent_id', $agent->id)->pending()->count(),
                'last_call'      => $calls()->latest('id')->first(),
            ],
            'recentCalls' => $calls()->with('customer')->latest('id')->limit(8)->get(),
            // Campaigns this agent actually dialled for. Derived from its calls
            // because a campaign records no agent of its own -- so this reflects
            // real activity rather than an assumed link.
            'campaigns'   => $this->campaignsFor($agent),
            'callbackList' => Callback::with('customer')->where('agent_id', $agent->id)
                ->pending()->orderBy('scheduled_at')->limit(5)->get(),
            // Only languages this agent has actually spoken, plus its configured
            // default. Nothing invented.
            'languages' => $calls()->whereNotNull('language')->distinct()->pluck('language')
                ->push($agent->default_language)
                ->filter()
                ->unique()
                ->values(),
        ]);
    }

    /**
     * The campaigns this agent has placed calls under.
     *
     * @return \Illuminate\Support\Collection<int,array{name:string,status:?string,calls:int,answered:int,interested:int}>
     */
    private function campaignsFor(Agent $agent): \Illuminate\Support\Collection
    {
        $rows = CallAttempt::where('agent_id', $agent->id)
            ->whereNotNull('campaign_id')
            ->selectRaw(
                "campaign_id,
                 COUNT(*) AS calls,
                 SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS answered,
                 SUM(CASE WHEN lead_generated = 1 OR call_disposition = 'interested' THEN 1 ELSE 0 END) AS interested"
            )
            ->groupBy('campaign_id')
            ->orderByDesc('calls')
            ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        // Our own campaign names, keyed by the reference stored on the call.
        $names = Campaign::whereIn('sarvam_campaign_id', $rows->pluck('campaign_id'))
            ->get()
            ->keyBy('sarvam_campaign_id');

        return $rows->map(fn ($r) => [
            'name'       => $names[$r->campaign_id]->name ?? 'Campaign',
            'status'     => $names[$r->campaign_id]->status ?? null,
            'model'      => $names[$r->campaign_id] ?? null,
            'calls'      => (int) $r->calls,
            'answered'   => (int) $r->answered,
            'interested' => (int) $r->interested,
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
