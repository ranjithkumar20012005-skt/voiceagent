<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\AgentBuildRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The queue of "build it for us" requests, across every workspace at once.
 *
 * Not inside the `workspace` group, same as ClientController: an
 * administrator is triaging requests for every client, so there is no single
 * tenant to scope to, and route-model binding resolves unscoped because
 * Tenancy is never set for this group.
 */
class AgentRequestController extends Controller
{
    public function index(): View
    {
        $requests = AgentBuildRequest::query()
            ->with(['workspace', 'requestedBy', 'fulfilledAgent'])
            ->orderByRaw("status = 'pending' desc, status = 'in_progress' desc")
            ->latest()
            ->get();

        return view('internal.agent_requests.index', ['requests' => $requests]);
    }

    public function update(Request $request, AgentBuildRequest $agentBuildRequest)
    {
        $data = $request->validate([
            'status'             => ['required', Rule::in(array_keys(AgentBuildRequest::STATUSES))],
            'admin_note'         => ['nullable', 'string', 'max:2000'],
            'fulfilled_agent_id' => ['nullable', 'integer'],
        ]);

        // An agent picked here must belong to the same workspace as the request --
        // never trust the posted id on its own.
        if (! empty($data['fulfilled_agent_id'])) {
            $data['fulfilled_agent_id'] = $agentBuildRequest->workspace->agents()
                ->whereKey($data['fulfilled_agent_id'])
                ->value('id');
        }

        $agentBuildRequest->forceFill($data)->save();

        return back()->with(
            'status',
            "Request '{$agentBuildRequest->title}' marked {$agentBuildRequest->displayStatus()}."
        );
    }
}
