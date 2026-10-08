<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCallRequest;
use App\Models\Agent;
use App\Models\CallAttempt;
use App\Models\Customer;
use App\Services\Presenters\CallResultPresenter;
use App\Services\Presenters\TranscriptPresenter;
use App\Services\SarvamException;
use App\Services\SarvamVoiceService;
use App\Support\CallStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CallController extends Controller
{
    public function __construct(private readonly SarvamVoiceService $sarvam)
    {
    }

    // =================================================================
    // Call history
    // =================================================================

    public function index(Request $request): View
    {
        $filter = $request->query('filter');

        $query = CallAttempt::with(['customer', 'agent'])->latest('id');

        if ($agentId = $request->query('agent')) {
            $agentId === 'workspace'
                ? $query->whereNull('agent_id')
                : $query->where('agent_id', (int) $agentId);
        }

        // One filter control drives both concepts; connectivity values and
        // outcome values never collide.
        if ($filter && $filter !== 'all') {
            if (array_key_exists($filter, CallStatus::connectivityLabels())) {
                $query->where('connectivity_status', $filter);
            } elseif (array_key_exists($filter, CallStatus::outcomeLabels())) {
                $query->where('call_disposition', $filter);
            }
        }

        if ($search = $request->query('q')) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('customer_phone_number', 'like', $like)
                  ->orWhere('attempt_id', 'like', $like)
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)
                      ->orWhere('policy_number', 'like', $like));
            });
        }

        if (in_array($request->query('direction'), ['inbound', 'outbound'], true)) {
            $query->where('direction', $request->query('direction'));
        }

        if ($from = $request->date('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return view('calls.index', [
            'attempts' => $query->paginate(25)->withQueryString(),
            'filter'   => $filter ?: 'all',
            'search'   => $search,
            'agents'   => Agent::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * The full result of one call.
     *
     * Everything is read through the presenters, so no provider payload, attempt
     * identifier or raw JSON reaches the view.
     */
    public function show(CallAttempt $call): View
    {
        $call->load(['customer', 'agent', 'callback']);

        return view('calls.show', [
            'call'       => $call,
            'result'     => CallResultPresenter::for($call),
            'transcript' => TranscriptPresenter::for($call),
            'callback'   => $call->callback,
        ]);
    }

    // =================================================================
    // Call Now
    // =================================================================

    /**
     * Place a single immediate outbound call.
     *
     * The browser only ever talks to this endpoint -- it never sees the voice
     * platform's URL, key or payload shape.
     */
    public function store(StoreCallRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! $this->sarvam->isConfigured()) {
            return response()->json([
                'ok'      => false,
                'message' => 'The voice service is not configured yet. Ask an administrator to complete the server setup.',
            ], 422);
        }

        // Guard against a double-clicked "Start Call" replaying the same call.
        $lockKey = 'call:lock:' . sha1(($data['idempotency_key'] ?? '') . '|' . $data['phone_number'] . '|' . $request->user()->id);

        if (! Cache::add($lockKey, true, now()->addSeconds(30))) {
            return response()->json([
                'ok'      => false,
                'message' => 'That call was already submitted a moment ago.',
            ], 429);
        }

        $customer = $this->resolveCustomer($data);

        if ($customer && $customer->do_not_call) {
            Cache::forget($lockKey);

            return response()->json([
                'ok'      => false,
                'message' => 'This customer is marked Do Not Call.',
            ], 422);
        }

        $variables = array_merge(
            $customer ? $customer->agentVariables() : $this->adHocVariables($data),
            $request->extraVariables(),
        );

        // The operator's pick, else the default agent, else the workspace
        // agent from the environment (agent stays null -- the original path).
        $agent = ! empty($data['agent_id'])
            ? Agent::find($data['agent_id'])
            : Agent::active()->where('is_default', true)->first();

        $language = $data['language']
            ?? $customer?->preferred_language
            ?? $agent?->default_language
            ?? config('sarvam.default_language');

        // Record the intent first so a call always has a local row, even if
        // the API call then fails.
        $attempt = CallAttempt::create([
            'customer_id'             => $customer?->id,
            'agent_id'                => $agent?->id,
            'direction'               => 'outbound',
            'status'                  => CallStatus::QUEUED,
            'customer_phone_number'   => $data['phone_number'],
            'agent_phone_number'      => config('sarvam.agent_phone_number'),
            'initial_agent_variables' => $variables,
            'user_id'                 => $request->user()->id,
        ]);

        try {
            $result = $this->sarvam->createInstantCall(
                phoneNumber: $data['phone_number'],
                agentVariables: $variables,
                language: $language,
                webhookMetadata: array_filter([
                    'customer_id'     => $customer?->id ? (string) $customer->id : null,
                    'call_attempt_id' => (string) $attempt->id,
                ]),
                app: $agent?->providerApp(),
            );
        } catch (SarvamException $e) {
            // Full upstream detail goes to the server log, never to the browser.
            Log::warning('call.instant_failed', [
                'call_attempt_id' => $attempt->id,
                'upstream_status' => $e->status,
                'upstream_detail' => $e->upstreamDetail(),
            ]);

            $attempt->update([
                'status'         => CallStatus::FAILED,
                'failure_reason' => $e->upstreamDetail(),
            ]);

            Cache::forget($lockKey);

            // Map the upstream status onto an honest local status instead of
            // labelling every failure a gateway error.
            // clientMessage(), not userMessage(): the latter names the provider
            // and, for 401/402/429, our credentials and our account balance.
            // Internal staff still get the operator wording.
            $internal = (bool) $request->user()?->is_internal_admin;

            return response()->json(array_filter([
                'ok'      => false,
                'message' => $internal ? $e->userMessage() : $e->clientMessage(),
                // Only in debug builds does the operator see the raw upstream text.
                'detail'  => config('app.debug') && $internal ? $e->upstreamDetail() : null,
            ], fn ($v) => $v !== null), $e->responseStatus());
        }

        $attempt->update([
            'attempt_id' => $result['attempt_id'],
            'status'     => CallStatus::DISPATCHED,
            'started_at' => now(),
        ]);

        $customer?->update([
            'customer_status' => 'in_progress',
            'last_call_at'    => now(),
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Call started. The result will appear here once the call completes.',
            'call'    => [
                'id'         => $attempt->id,
                'attempt_id' => $attempt->attempt_id,
                'phone'      => $attempt->display_phone,
            ],
        ]);
    }

    /** Reuse an existing customer where we can; otherwise create one so the
     *  call still lands in the CRM rather than floating free. */
    private function resolveCustomer(array $data): ?Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::find($data['customer_id']);
        }

        $existing = Customer::where('phone_number', $data['phone_number'])->first();

        if ($existing) {
            // Fill in anything the operator typed that we did not already know.
            foreach (['name', 'policy_number', 'registered_mobile'] as $field) {
                if (! empty($data[$field]) && blank($existing->{$field})) {
                    $existing->{$field} = $data[$field];
                }
            }

            if (! empty($data['language']) && blank($existing->preferred_language)) {
                $existing->preferred_language = $data['language'];
            }

            $existing->save();

            return $existing;
        }

        if (empty($data['name']) && empty($data['policy_number'])) {
            return null; // a bare number -- do not manufacture a CRM record
        }

        return Customer::create([
            'name'               => $data['name'] ?? null,
            'phone_number'       => $data['phone_number'],
            'policy_number'      => $data['policy_number'] ?? null,
            'registered_mobile'  => $data['registered_mobile'] ?? null,
            'preferred_language' => $data['language'] ?? null,
            'customer_status'    => 'pending',
        ]);
    }

    /** @return array<string,string> */
    private function adHocVariables(array $data): array
    {
        $map = (array) config('sarvam.agent_variables', []);
        $out = [];

        $source = [
            'name'               => $data['name'] ?? null,
            'phone_number'       => $data['phone_number'] ?? null,
            'policy_number'      => $data['policy_number'] ?? null,
            'registered_mobile'  => $data['registered_mobile'] ?? null,
            'preferred_language' => $data['language'] ?? null,
        ];

        foreach ($map as $sarvamKey => $localField) {
            if (! empty($source[$localField])) {
                $out[$sarvamKey] = (string) $source[$localField];
            }
        }

        return $out;
    }
}
