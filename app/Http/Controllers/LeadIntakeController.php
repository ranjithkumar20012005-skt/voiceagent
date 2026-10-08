<?php

namespace App\Http\Controllers;

use App\Jobs\PlaceInstantCallJob;
use App\Models\AgentLeadSource;
use App\Services\LeadIntake;
use App\Support\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The public endpoint a lead source posts to.
 *
 * Public by necessity -- a website form or an automation tool has no session --
 * so it is defended by the per-source token in the URL, compared in constant
 * time, plus rate limiting on the route and strict payload handling.
 *
 * It does exactly two things: record the lead and queue the call. The provider
 * is never called inside this request, so a slow platform cannot turn into a
 * timeout on somebody else's webhook and a retry storm.
 */
class LeadIntakeController extends Controller
{
    public function __construct(
        private readonly LeadIntake $intake,
        private readonly Tenancy $tenancy,
    ) {
    }

    public function store(Request $request, string $token): JsonResponse
    {
        $source = $this->resolve($token);

        if (! $source) {
            // 404 rather than 401: an unknown token should not learn that the
            // endpoint exists and is merely unauthorised.
            return response()->json(['error' => 'not found'], 404);
        }

        $payload = $request->isJson() ? $request->json()->all() : $request->all();

        if (! is_array($payload) || $payload === []) {
            return response()->json(['error' => 'empty payload'], 422);
        }

        $agent = $source->agent;

        if (! $agent) {
            return response()->json(['error' => 'not found'], 404);
        }

        // Everything below runs as the client that owns the source, so the
        // customer and the call attempt land in the right workspace.
        return $this->tenancy->actingAs($agent->workspace, function () use ($source, $payload, $agent) {
            $result = $this->intake->capture($source, $payload);

            if (! $result['customer']) {
                // 202, not 4xx: the lead arrived and was recorded as rejected.
                // A 4xx makes most providers retry the same unusable payload.
                return response()->json([
                    'accepted' => false,
                    'reason'   => $result['reason'],
                ], 202);
            }

            PlaceInstantCallJob::dispatch($agent->id, $result['customer']->id, $source->id);

            Log::info('lead.accepted', [
                'workspace_id' => $source->workspace_id,
                'agent_id'     => $agent->id,
                'action'       => 'intake_lead',
                'source'       => $source->kind,
            ]);

            return response()->json(['accepted' => true, 'queued' => true], 202);
        });
    }

    /**
     * Meta's lead-ads webhooks verify a subscription with a GET challenge before
     * they will deliver anything, so the same URL answers it.
     */
    public function verify(Request $request, string $token)
    {
        $source = $this->resolve($token);

        if (! $source) {
            return response('', 404);
        }

        $expected = $source->metadata['verify_token'] ?? null;
        $supplied = $request->query('hub_verify_token');

        if (! is_string($expected) || ! is_string($supplied) || ! hash_equals($expected, $supplied)) {
            return response('', 403);
        }

        return response((string) $request->query('hub_challenge', ''), 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Looks the source up by token, across tenants.
     *
     * Unscoped on purpose: an inbound webhook has no session, so finding the
     * owner IS the job here. The token is the only thing that authorises it.
     */
    private function resolve(string $token): ?AgentLeadSource
    {
        if (strlen($token) < 32) {
            return null;
        }

        $source = AgentLeadSource::withoutGlobalScope('workspace')
            ->with(['agent.workspace'])
            ->enabled()
            ->where('token', $token)
            ->first();

        // Constant-time confirmation, so a near-miss token cannot be narrowed
        // down by timing the response.
        if (! $source || ! hash_equals($source->token, $token)) {
            return null;
        }

        return $source->agent?->workspace ? $source : null;
    }
}
