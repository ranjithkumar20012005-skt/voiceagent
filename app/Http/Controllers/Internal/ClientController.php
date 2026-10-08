<?php

namespace App\Http\Controllers\Internal;

use App\Contracts\VoiceAgentProviderInterface;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\CallAttempt;
use App\Models\Membership;
use App\Models\PhoneNumber;
use App\Models\Workspace;
use App\Services\ClientProvisioner;
use App\Services\NumberUnavailableException;
use App\Services\PhoneNumberAllocator;
use App\Support\PhoneNumber as SupportPhoneNumber;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Our own team's view of the clients we run.
 *
 * This is the only place provider identifiers are entered or displayed. Every
 * query here deliberately bypasses the workspace scope -- an administrator is
 * acting across tenants, which is exactly what the scope prevents everywhere
 * else -- so the `admin` middleware on these routes is what keeps it closed.
 */
class ClientController extends Controller
{
    public function __construct(
        private readonly ClientProvisioner $provisioner,
        private readonly VoiceAgentProviderInterface $provider,
        private readonly Tenancy $tenancy,
    ) {
    }

    public function index(): View
    {
        // Only memberships are counted through the relation: Agent carries the
        // workspace scope, so withCount() on it would be filtered to the
        // administrator's own workspace and report zero for everyone else.
        $workspaces = Workspace::query()
            ->withCount('memberships as users_count')
            ->orderBy('name')
            ->get();

        // Agent and call figures per workspace, read across tenants on purpose.
        $agents = Agent::withoutGlobalScope('workspace')
            ->orderByDesc('is_default')
            ->get()
            ->groupBy('workspace_id');

        $callCounts = CallAttempt::withoutGlobalScope('workspace')
            ->selectRaw('workspace_id, COUNT(*) AS total')
            ->groupBy('workspace_id')
            ->pluck('total', 'workspace_id');

        return view('internal.clients.index', [
            'workspaces' => $workspaces,
            'agents'     => $agents,
            'callCounts' => $callCounts,
            'configured' => $this->provider->isConfigured(),
        ]);
    }

    public function create(): View
    {
        return view('internal.clients.create', [
            'languages' => config('sarvam.languages', []),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name'    => ['required', 'string', 'max:160'],
            'contact_name'     => ['required', 'string', 'max:120'],
            'email'            => ['required', 'email:rfc', 'max:191', 'unique:users,email'],
            'password'         => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'default_language' => ['nullable', 'string', 'max:32'],
        ]);

        $result = $this->provisioner->createClient($data);

        // The generated password is shown once, in the flash message, and is
        // never persisted in readable form or logged.
        $message = "Client created. Sign-in: {$result['user']->email}";

        if ($result['generated_password']) {
            $message .= " / {$result['generated_password']} (shown once -- copy it now)";
        }

        return redirect()
            ->route('internal.clients.show', $result['workspace'])
            ->with('status', $message);
    }

    public function show(Workspace $workspace): View
    {
        // Read the client's own records as that client, so relations and counts
        // resolve under the same scope the client would see.
        [$agents, $numbers, $calls] = $this->tenancy->actingAs($workspace, fn () => [
            Agent::with('phoneNumber')->orderByDesc('is_default')->get(),
            PhoneNumber::ownedBy($workspace)->get(),
            CallAttempt::count(),
        ]);

        return view('internal.clients.show', [
            'workspace'  => $workspace,
            'agents'     => $agents,
            'numbers'    => $numbers,
            'callCount'  => $calls,
            'pool'       => PhoneNumber::inPool()->orderBy('phone_number')->get(),
            'members'    => $workspace->memberships()->with('user')->get(),
            'languages'  => config('sarvam.languages', []),
            'modes'      => Agent::CALLING_MODES,
            'configured' => $this->provider->isConfigured(),
            // Internal diagnostics, admin-only.
            'status'     => $agents->mapWithKeys(fn (Agent $a) => [$a->id => $this->provider->getAgent($a)]),
        ]);
    }

    /** Map this client's workspace to the hosted agent our team built for them. */
    public function mapAgent(Request $request, Workspace $workspace)
    {
        $data = $request->validate([
            'agent_id'               => ['nullable', 'integer'],
            'display_name'           => ['required', 'string', 'max:80'],
            'description'            => ['nullable', 'string', 'max:255'],
            'provider_agent_id'      => ['required', 'string', 'max:191'],
            'provider_agent_version' => ['required', 'integer', 'min:1'],
            'provider_deployment_id' => ['nullable', 'string', 'max:191'],
            'default_language'       => ['nullable', 'string', 'max:32'],
            'calling_mode'           => ['required', Rule::in(array_keys(Agent::CALLING_MODES))],
        ]);

        $existing = null;

        if (! empty($data['agent_id'])) {
            $existing = Agent::withoutGlobalScope('workspace')
                ->where('workspace_id', $workspace->id)
                ->whereKey($data['agent_id'])
                ->firstOrFail();
        }

        $agent = $this->provisioner->mapAgent($workspace, $data, $existing);

        return redirect()->route('internal.clients.show', $workspace)->with(
            'status',
            $agent->status === Agent::ERROR
                ? "Mapping saved but not usable: {$agent->error_message}"
                : "Agent '{$agent->name}' mapped and ready.",
        );
    }

    public function addUser(Request $request, Workspace $workspace)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email:rfc', 'max:191', 'unique:users,email'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'role'     => ['required', Rule::in([Membership::OWNER, Membership::ADMIN, Membership::MEMBER])],
        ]);

        $result = $this->provisioner->addClientUser(
            $workspace,
            $data['name'],
            $data['email'],
            $data['password'] ?? null,
            $data['role'],
        );

        $message = "User added: {$result['user']->email}";

        if ($result['generated_password']) {
            $message .= " / {$result['generated_password']} (shown once -- copy it now)";
        }

        return redirect()->route('internal.clients.show', $workspace)->with('status', $message);
    }

    /** Pause or resume a client's agent. */
    public function setAgentStatus(Request $request, Workspace $workspace, int $agentId)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([Agent::ACTIVE, Agent::PAUSED, Agent::READY, Agent::ARCHIVED])],
        ]);

        $agent = Agent::withoutGlobalScope('workspace')
            ->where('workspace_id', $workspace->id)
            ->whereKey($agentId)
            ->firstOrFail();

        $agent->forceFill(['status' => $data['status']])->save();

        return back()->with('status', "Agent '{$agent->name}' is now {$agent->displayStatus()}.");
    }

    /**
     * Import numbers into the shared pool.
     *
     * The platform publishes no endpoint for listing the numbers our account
     * owns -- renting and importing them is a dashboard action -- so the pool is
     * stocked here by hand and stays our source of truth.
     */
    public function importNumbers(Request $request)
    {
        $data = $request->validate([
            'numbers' => ['required', 'string', 'max:4000'],
            'country' => ['required', 'string', 'size:2'],
        ]);

        $lines = preg_split('/[\r\n,]+/', $data['numbers']) ?: [];
        $added = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            $number = SupportPhoneNumber::normalize(trim($line));

            if (! $number) {
                $skipped++;
                continue;
            }

            $existing = PhoneNumber::where('phone_number', $number)->exists();

            if ($existing) {
                $skipped++;
                continue;
            }

            PhoneNumber::create([
                'phone_number' => $number,
                'country'      => strtoupper($data['country']),
                'provider'     => config('voice.provider', 'sarvam'),
                'status'       => PhoneNumber::AVAILABLE,
                'capabilities' => ['voice'],
            ]);

            $added++;
        }

        return back()->with('status', "{$added} number(s) added to the pool, {$skipped} skipped.");
    }

    /** Give a pooled number to a client and attach it to one of their agents. */
    public function assignNumber(Request $request, Workspace $workspace, PhoneNumberAllocator $allocator)
    {
        $data = $request->validate([
            'phone_number_id' => ['required', 'integer'],
            'agent_id'        => ['required', 'integer'],
        ]);

        $agent = Agent::withoutGlobalScope('workspace')
            ->where('workspace_id', $workspace->id)
            ->whereKey($data['agent_id'])
            ->firstOrFail();

        try {
            $claimed = $allocator->claim((int) $data['phone_number_id'], $workspace);
            $result  = $allocator->attachToAgent($claimed, $agent);
        } catch (NumberUnavailableException $e) {
            return back()->withErrors(['phone_number_id' => $e->getMessage()]);
        }

        $note = $result['provider_synced']
            ? 'Number assigned and mapped on the platform.'
            : 'Number assigned. Platform mapping is pending and will be reconciled.';

        return back()->with('status', $note);
    }

    public function releaseNumber(Workspace $workspace, int $numberId, PhoneNumberAllocator $allocator)
    {
        $number = PhoneNumber::ownedBy($workspace)->whereKey($numberId)->firstOrFail();

        $allocator->release($number);

        return back()->with('status', 'Number returned to the pool.');
    }

    /** Toggle a client workspace on or off. */
    public function setWorkspaceStatus(Request $request, Workspace $workspace)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);

        $workspace->forceFill(['status' => $data['status']])->save();

        return back()->with('status', "{$workspace->name} is now {$data['status']}.");
    }
}
