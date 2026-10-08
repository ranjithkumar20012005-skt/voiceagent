<?php

namespace App\Http\Controllers;

use App\Services\CallResultProcessor;
use App\Services\CallWorkspaceResolver;
use App\Support\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives call-completion callbacks from the voice platform.
 *
 * Public by necessity (no session), so it is defended by:
 *   - a secret path token compared in constant time
 *   - an app_id allow-check when the payload carries one
 *   - strict payload-shape validation
 *   - rate limiting (see routes/api.php)
 *   - idempotency on attempt_id (see CallResultProcessor)
 *
 * Nothing in the payload is trusted beyond the fields we explicitly read.
 */
class SarvamWebhookController extends Controller
{
    public function __construct(
        private readonly CallResultProcessor $processor,
        private readonly CallWorkspaceResolver $resolver,
        private readonly Tenancy $tenancy,
    ) {
    }

    public function handle(Request $request, string $token): JsonResponse
    {
        $expected = (string) config('sarvam.webhook_token');

        // A blank configured token must never mean "accept everything".
        if ($expected === '' || ! hash_equals($expected, $token)) {
            Log::warning('sarvam.webhook.rejected', [
                'reason' => 'bad_token',
                'ip'     => $request->ip(),
            ]);

            return response()->json(['error' => 'not found'], 404);
        }

        $payload = $request->json()->all();

        if (! is_array($payload) || $payload === []) {
            return response()->json(['error' => 'invalid payload'], 422);
        }

        // attempt_id is the one field we genuinely cannot work without.
        $attemptId = $payload['attempt_id'] ?? null;

        if (! is_string($attemptId) || trim($attemptId) === '' || strlen($attemptId) > 191) {
            return response()->json(['error' => 'attempt_id is required'], 422);
        }

        // The agent in the payload identifies the client: each one has their own
        // hosted agent in our account. An agent we do not know is not ours.
        $payloadApp = $payload['app_id'] ?? null;
        $resolved   = $this->resolver->resolve(is_string($payloadApp) ? $payloadApp : null);

        if (! $resolved) {
            Log::warning('sarvam.webhook.rejected', [
                'reason'     => 'app_id_mismatch',
                'attempt_id' => $attemptId,
            ]);

            return response()->json(['error' => 'unknown app'], 422);
        }

        // Transcript, when present, must be a list of turn objects.
        if (isset($payload['interaction_transcript']) && ! is_array($payload['interaction_transcript'])) {
            return response()->json(['error' => 'invalid transcript'], 422);
        }

        try {
            // Processed as the resolved client, so the call attempt, its customer
            // and anything else written here are stamped with that workspace and
            // land in the right dashboard.
            $result = $this->tenancy->actingAs(
                $resolved['workspace'],
                fn () => $this->processor->process($payload, $resolved['agent']),
            );
        } catch (\Throwable $e) {
            Log::error('sarvam.webhook.failed', [
                'workspace_id' => $resolved['workspace']->id,
                'attempt_id'   => $attemptId,
                'message'      => $e->getMessage(),
            ]);

            // 500 lets the platform retry delivery; processing is idempotent.
            return response()->json(['error' => 'processing failed'], 500);
        }

        Log::info('sarvam.webhook.received', [
            'workspace_id' => $resolved['workspace']->id,
            'agent_id'     => $resolved['agent']?->id,
            'attempt_id'   => $result['attempt_id'],
            'created'      => $result['created'],
            'duplicate'    => $result['duplicate'],
        ]);

        return response()->json([
            'ok'        => true,
            'duplicate' => $result['duplicate'],
        ]);
    }
}
