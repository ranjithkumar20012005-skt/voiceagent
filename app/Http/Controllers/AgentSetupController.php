<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchCampaignJob;
use App\Models\Agent;
use App\Models\Automation;
use App\Models\AgentLeadSource;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Services\CallingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * What happens after an agent is created: how it gets people to call.
 *
 * Two routes, matching how the agent will actually be used:
 *
 *   Instant leads - connect a source (website form, lead ads, automation tool)
 *                   and every new lead is called within seconds of arriving.
 *   Bulk campaign - upload a list, review it, then start the agent on it.
 *
 * The bulk path deliberately reuses the existing import pipeline rather than
 * growing a second uploader: that pipeline already normalises phone numbers,
 * de-duplicates, validates and reports rejected rows with reasons.
 */
class AgentSetupController extends Controller
{
    public function show(Agent $agent): View
    {
        $agent->load(['phoneNumber', 'leadSources']);

        return view('agents.setup', [
            'agent'    => $agent,
            'schedule' => CallingSchedule::for($agent),
            'sources'  => $agent->leadSources()->latest('id')->get(),
            'kinds'    => AgentLeadSource::KINDS,
            // Lists already imported and ready to call.
            'batches'  => ImportBatch::where('status', ImportBatch::COMPLETED)
                ->where('valid_rows', '>', 0)
                ->latest('id')
                ->limit(10)
                ->get(),
            'campaigns' => Campaign::latest('id')->limit(5)->get(),
            'callableCustomers' => Customer::where('do_not_call', false)->count(),
            'schedules' => Automation::where('agent_id', $agent->id)->latest('id')->get(),
        ]);
    }

    /** Choose how this agent gets its people, and when it may call them. */
    public function update(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'calling_mode'         => ['required', Rule::in(array_keys(Agent::CALLING_MODES))],
            'is_always_on'         => ['nullable', 'boolean'],
            'calling_window_start' => ['nullable', 'date_format:H:i'],
            'calling_window_end'   => ['nullable', 'date_format:H:i'],
            'calling_days'         => ['nullable', 'array'],
            'calling_days.*'       => [Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'timezone'             => ['nullable', 'timezone'],
        ]);

        $alwaysOn = $request->boolean('is_always_on');

        // A window with no days allowed would silently never open, so an empty
        // selection falls back to every day rather than blocking every call.
        $days = $data['calling_days'] ?? [];

        $agent->fill([
            'calling_mode'         => $data['calling_mode'],
            'is_always_on'         => $alwaysOn,
            'calling_window_start' => $alwaysOn ? null : ($data['calling_window_start'] ?? '09:00'),
            'calling_window_end'   => $alwaysOn ? null : ($data['calling_window_end'] ?? '20:00'),
            'calling_days'         => $alwaysOn ? null : ($days !== [] ? array_values($days) : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']),
            'timezone'             => $data['timezone'] ?? $agent->workspace?->timezone,
        ])->save();

        return back()->with('status', 'Calling setup saved. ' . CallingSchedule::for($agent->refresh())->describe() . '.');
    }

    // ---------------------------------------------------------------
    // Instant leads
    // ---------------------------------------------------------------

    public function addSource(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:80'],
            'kind'       => ['required', Rule::in(array_keys(AgentLeadSource::KINDS))],
            'map_phone'  => ['nullable', 'string', 'max:60'],
            'map_name'   => ['nullable', 'string', 'max:60'],
            'map_email'  => ['nullable', 'string', 'max:60'],
        ]);

        $source = new AgentLeadSource([
            'agent_id'  => $agent->id,
            'name'      => $data['name'],
            'kind'      => $data['kind'],
            'enabled'   => true,
            'field_map' => array_filter([
                'phone' => $data['map_phone'] ?? null,
                'name'  => $data['map_name'] ?? null,
                'email' => $data['map_email'] ?? null,
            ]),
            // Lead-ads providers verify a subscription before delivering.
            'metadata'  => ['verify_token' => \Illuminate\Support\Str::random(24)],
        ]);

        $source->workspace_id = $agent->workspace_id;
        $source->save();

        return back()->with('status', "'{$source->name}' connected. Point it at the URL shown below.");
    }

    public function toggleSource(Agent $agent, AgentLeadSource $source)
    {
        abort_unless((int) $source->agent_id === (int) $agent->id, 404);

        $source->forceFill(['enabled' => ! $source->enabled])->save();

        return back()->with('status', $source->enabled ? 'Lead source resumed.' : 'Lead source paused.');
    }

    public function removeSource(Agent $agent, AgentLeadSource $source)
    {
        abort_unless((int) $source->agent_id === (int) $agent->id, 404);

        $source->delete();

        return back()->with('status', 'Lead source removed. Its URL stops working immediately.');
    }

    // ---------------------------------------------------------------
    // Bulk campaign
    // ---------------------------------------------------------------

    /**
     * Start the agent on a list.
     *
     * Creates the campaign and queues the dispatch; nothing is dialled inside
     * this request. The existing DispatchCampaignJob does the work, so retries,
     * chunking and rate limiting are the ones already in production.
     */
    public function startCampaign(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:80'],
            'import_batch_id'  => ['nullable', 'integer'],
            'max_calls'        => ['required', 'integer', 'min:1', 'max:5000'],
        ]);

        abort_unless($agent->canPlaceCalls(), 422, 'This agent is not ready to place calls yet.');

        $customers = Customer::query()
            ->where('do_not_call', false)
            ->when($data['import_batch_id'] ?? null, fn ($q, $id) => $q->where('import_batch_id', $id))
            ->orderBy('id')
            ->limit($data['max_calls'])
            ->pluck('id');

        if ($customers->isEmpty()) {
            return back()->withErrors(['import_batch_id' => 'That list has nobody left to call.']);
        }

        $campaign = DB::transaction(function () use ($data, $agent, $customers) {
            $campaign = new Campaign([
                'name'            => $data['name'],
                'status'          => 'queued',
                'import_batch_id' => $data['import_batch_id'] ?? null,
                'total_contacts'  => $customers->count(),
            ]);

            $campaign->workspace_id = $agent->workspace_id;
            $campaign->save();

            return $campaign;
        });

        // Held until the calling window opens, so starting a campaign at night
        // does not ring anyone at night.
        $delay = CallingSchedule::for($agent)->delaySeconds();

        DispatchCampaignJob::dispatch($campaign->id, $customers->all())
            ->delay($delay > 0 ? now()->addSeconds($delay) : null);

        return redirect()->route('agents.setup', $agent)->with(
            'status',
            $delay > 0
                ? "'{$campaign->name}' is queued and will start when the calling window opens."
                : "'{$campaign->name}' has started. {$customers->count()} people queued.",
        );
    }

    // ---------------------------------------------------------------
    // Scheduled calling
    // ---------------------------------------------------------------

    /**
     * A recurring run: wake at a set time, take the list, call it, follow up.
     *
     * Saved as an Automation, which already carries the run time, timezone,
     * calling window, per-run cap, retry limit and minimum gap between calls --
     * and is already executed by the `calls:dispatch-due` scheduler.
     */
    public function saveSchedule(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'schedule_id'      => ['nullable', 'integer'],
            'name'             => ['required', 'string', 'max:80'],
            'enabled'          => ['nullable', 'boolean'],
            'run_at'           => ['required', 'date_format:H:i'],
            'run_days'         => ['nullable', 'array'],
            'run_days.*'       => [Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'import_batch_id'  => ['nullable', 'integer'],
            'max_calls_per_run' => ['required', 'integer', 'min:1', 'max:5000'],
            'only_unreached'   => ['nullable', 'boolean'],
            'max_retries'      => ['required', 'integer', 'min:0', 'max:10'],
            'min_days_between_calls' => ['required', 'integer', 'min:0', 'max:60'],
        ]);

        $schedule = $data['schedule_id']
            ? Automation::where('agent_id', $agent->id)->whereKey($data['schedule_id'])->firstOrFail()
            : new Automation();

        $schedule->fill([
            'agent_id'        => $agent->id,
            'name'            => $data['name'],
            'enabled'         => $request->boolean('enabled'),
            'frequency'       => 'daily',
            'run_at'          => $data['run_at'],
            'run_days'        => $data['run_days'] ?? null,
            'timezone'        => $agent->timezone ?: ($agent->workspace?->timezone ?: config('app.timezone')),
            'import_batch_id' => $data['import_batch_id'] ?: null,
            'max_calls_per_run' => $data['max_calls_per_run'],
            'only_unreached'  => $request->boolean('only_unreached'),
            'max_retries'     => $data['max_retries'],
            'min_days_between_calls' => $data['min_days_between_calls'],
            // The schedule calls inside the agent's own hours, so the two cannot
            // disagree about when this client may be rung.
            'window_start'    => $agent->is_always_on ? '00:00' : ($agent->calling_window_start ?: '09:00'),
            'window_end'      => $agent->is_always_on ? '23:59' : ($agent->calling_window_end ?: '20:00'),
            // Not an insurance sweep, so the renewal-specific filters are off.
            'expiry_within_days' => 0,
            'skip_already_renewed' => false,
            'skip_do_not_call'   => true,
            'skip_active_callback' => true,
        ]);

        $schedule->workspace_id = $agent->workspace_id;
        $schedule->save();

        return back()->with(
            'status',
            $schedule->enabled
                ? "'{$schedule->name}' will run every day at {$schedule->run_at} ({$schedule->timezone})."
                : "'{$schedule->name}' saved but switched off.",
        );
    }

    public function deleteSchedule(Agent $agent, Automation $schedule)
    {
        abort_unless((int) $schedule->agent_id === (int) $agent->id, 404);

        $schedule->delete();

        return back()->with('status', 'Schedule removed.');
    }

    /** Run it now rather than waiting for its time. */
    public function runSchedule(Agent $agent, Automation $schedule)
    {
        abort_unless((int) $schedule->agent_id === (int) $agent->id, 404);

        \Illuminate\Support\Facades\Artisan::call('calls:dispatch-due', [
            '--automation' => $schedule->id,
            '--force'      => true,
        ]);

        return back()->with('status', "'{$schedule->name}' run started. {$schedule->refresh()->last_run_message}");
    }

    // ---------------------------------------------------------------
    // Start / pause the agent itself
    // ---------------------------------------------------------------

    public function start(Agent $agent)
    {
        if (! $agent->isProvisioned()) {
            return back()->withErrors(['agent' => 'This agent is still being set up and cannot be started yet.']);
        }

        $agent->forceFill(['status' => Agent::ACTIVE])->save();

        return back()->with('status', "{$agent->name} is now active and will take calls.");
    }

    public function pause(Agent $agent)
    {
        $agent->forceFill(['status' => Agent::PAUSED])->save();

        return back()->with('status', "{$agent->name} is paused. No new calls will be placed.");
    }
}
