<?php

namespace App\Services;

use App\Models\CallAttempt;
use App\Models\Customer;
use App\Support\CallStatus;
use App\Support\PhoneNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Applies a Sarvam completion webhook to our own records.
 *
 * Handles both payload shapes (instant outbound and campaign) and is
 * idempotent on attempt_id: replaying the same webhook updates the same row
 * and never creates a second one, never double-counts a customer's call_count.
 */
class CallResultProcessor
{
    /**
     * @param  array<string,mixed>  $payload
     * @return array{attempt_id:string, created:bool, duplicate:bool}
     */
    public function process(array $payload): array
    {
        $attemptId = $this->str($payload['attempt_id'] ?? null);

        if ($attemptId === null) {
            throw new \InvalidArgumentException('Payload is missing attempt_id.');
        }

        return DB::transaction(function () use ($payload, $attemptId) {
            // Lock the row so two concurrent deliveries of the same webhook
            // cannot both pass the "already processed?" check.
            $attempt = CallAttempt::where('attempt_id', $attemptId)->lockForUpdate()->first();

            $created = false;

            if (! $attempt) {
                $attempt = new CallAttempt(['attempt_id' => $attemptId, 'direction' => 'outbound']);
                $created = true;
            }

            $alreadyFinal = $attempt->exists && $attempt->status === CallStatus::COMPLETED;

            $this->fillFromPayload($attempt, $payload);

            $attempt->status              = CallStatus::COMPLETED;
            $attempt->webhook_received_at = now();
            $attempt->raw_webhook_payload = $payload;
            $attempt->save();

            // Only move customer state forward the first time a result lands.
            if (! $alreadyFinal && $attempt->customer_id) {
                $this->updateCustomer($attempt);
            }

            return [
                'attempt_id' => $attemptId,
                'created'    => $created,
                'duplicate'  => $alreadyFinal,
            ];
        });
    }

    // =================================================================

    private function fillFromPayload(CallAttempt $attempt, array $payload): void
    {
        // --- identifiers -------------------------------------------------
        $attempt->interaction_id ??= $this->str($payload['interaction_id'] ?? null);
        $attempt->campaign_id    ??= $this->str($payload['campaign_id'] ?? null);
        $attempt->cohort_id      ??= $this->str($payload['cohort_id'] ?? null);

        // --- connectivity ------------------------------------------------
        // Campaign payloads use connectivity_status; instant outbound uses status.
        $rawConnectivity = $this->str($payload['connectivity_status'] ?? null)
            ?? $this->str($payload['status'] ?? null);

        if ($mapped = CallStatus::connectivityFrom($rawConnectivity)) {
            $attempt->connectivity_status = $mapped;
        } elseif ($rawConnectivity !== null) {
            // Unknown vocabulary: record it as failed-to-classify rather than
            // silently calling it "connected".
            Log::warning('sarvam.webhook.unmapped_connectivity', ['value' => $rawConnectivity]);
        }

        $attempt->completion_status = $this->str($payload['completion_status'] ?? null) ?? $attempt->completion_status;
        $attempt->failure_reason    = $this->str($payload['failure_reason'] ?? null) ?? $attempt->failure_reason;

        // --- timing ------------------------------------------------------
        if (isset($payload['duration']) && is_numeric($payload['duration'])) {
            $attempt->duration_seconds = (int) round((float) $payload['duration']);
        }

        if (isset($payload['retry_attempt']) && is_numeric($payload['retry_attempt'])) {
            $attempt->retry_attempt = (int) $payload['retry_attempt'];
        }

        $attempt->started_at = $this->date($payload['start_datetime'] ?? $payload['executed_at'] ?? null) ?? $attempt->started_at;
        $attempt->ended_at   = $this->date($payload['end_datetime'] ?? null) ?? $attempt->ended_at;

        // --- phone numbers -------------------------------------------------
        $agentPhone = $this->str($payload['agent_phone_number'] ?? null)
            ?? $this->str($payload['channel_info']['agent_phone_number'] ?? null);

        if ($agentPhone) {
            $attempt->agent_phone_number = PhoneNumber::normalize($agentPhone) ?? $agentPhone;
        }

        if ($userPhone = $this->str($payload['user_phone_number'] ?? null)) {
            $attempt->customer_phone_number = PhoneNumber::normalize($userPhone) ?? $userPhone;
        }

        // --- variables & transcript ---------------------------------------
        foreach (['initial_agent_variables', 'final_agent_variables', 'output_agent_variables'] as $key) {
            if (is_array($payload[$key] ?? null)) {
                $attempt->{$key} = $payload[$key];
            }
        }

        if (is_array($payload['interaction_transcript'] ?? null)) {
            $attempt->transcript = $payload['interaction_transcript'];
        }

        // --- business outcome ---------------------------------------------
        $this->applyOutputVariables($attempt);

        // --- link to a customer --------------------------------------------
        if (! $attempt->customer_id) {
            $attempt->customer_id = $this->resolveCustomerId($attempt, $payload);
        }
    }

    /**
     * Derive the business outcome strictly from the agent's explicit output
     * variables. We never read the transcript to guess a disposition.
     */
    private function applyOutputVariables(CallAttempt $attempt): void
    {
        $vars = array_merge(
            (array) ($attempt->final_agent_variables ?? []),
            (array) ($attempt->output_agent_variables ?? []),
        );

        if ($vars === []) {
            // No explicit signal. A connected call with nothing reported is
            // "unknown", not "not interested".
            if ($attempt->connectivity_status === CallStatus::CONNECTED && $attempt->call_disposition === null) {
                $attempt->call_disposition = CallStatus::UNKNOWN;
            }

            return;
        }

        $names = (array) config('sarvam.output_variables', []);

        $get = function (string $logical) use ($vars, $names) {
            $key = array_search($logical, $names, true) ?: $logical;

            return $vars[$key] ?? $vars[$logical] ?? null;
        };

        if (($d = $get('call_disposition')) !== null) {
            $attempt->call_disposition = CallStatus::outcomeFrom(is_scalar($d) ? (string) $d : null);
        } elseif ($attempt->connectivity_status === CallStatus::CONNECTED && $attempt->call_disposition === null) {
            $attempt->call_disposition = CallStatus::UNKNOWN;
        }

        if (($v = $get('lead_generated')) !== null) {
            $attempt->lead_generated = $this->bool($v);
        }

        if (($v = $get('callback_required')) !== null) {
            $attempt->callback_required = $this->bool($v);
        }

        if (($v = $get('callback_at')) !== null && ($when = $this->date($v))) {
            $attempt->callback_at = $when;
        }
    }

    /** Find the customer this result belongs to, without guessing wildly. */
    private function resolveCustomerId(CallAttempt $attempt, array $payload): ?int
    {
        // 1. Metadata we attached when we placed the call.
        $meta = $payload['webhook_config']['metadata'] ?? $payload['metadata'] ?? null;

        if (is_array($meta) && isset($meta['customer_id']) && is_numeric($meta['customer_id'])) {
            if ($c = Customer::find((int) $meta['customer_id'])) {
                return $c->id;
            }
        }

        // 2. The identifier we streamed into the cohort.
        if ($identifier = $this->str($payload['user_identifier'] ?? null)) {
            if ($c = Customer::where('customer_identifier', $identifier)->first()) {
                return $c->id;
            }
        }

        // 3. Fall back to the dialled number.
        if ($attempt->customer_phone_number) {
            if ($c = Customer::where('phone_number', $attempt->customer_phone_number)->first()) {
                return $c->id;
            }
        }

        return null;
    }

    /** Mirror the latest result onto the customer record for the CRM views. */
    private function updateCustomer(CallAttempt $attempt): void
    {
        $customer = $attempt->customer;

        if (! $customer) {
            return;
        }

        $customer->last_call_at      = $attempt->ended_at ?? $attempt->started_at ?? now();
        $customer->last_connectivity = $attempt->connectivity_status;
        $customer->call_count        = $customer->call_count + 1;

        if ($attempt->call_disposition) {
            $customer->last_outcome = $attempt->call_disposition;
        }

        if ($attempt->callback_required && $attempt->callback_at) {
            $customer->next_callback_at = $attempt->callback_at;
        } elseif ($attempt->call_disposition && $attempt->call_disposition !== CallStatus::CALLBACK) {
            $customer->next_callback_at = null;
        }

        // The agent explicitly asked us to stop calling this person.
        if ($attempt->call_disposition === CallStatus::DO_NOT_CALL) {
            $customer->do_not_call = true;
        }

        $customer->customer_status = match ($attempt->call_disposition) {
            CallStatus::ALREADY_RENEWED, CallStatus::NOT_INTERESTED, CallStatus::DO_NOT_CALL => 'closed',
            CallStatus::CALLBACK => 'pending',
            null     => $customer->customer_status,
            default  => 'contacted',
        };

        $customer->save();
    }

    // ---------------------------------------------------------------

    private function str(mixed $v): ?string
    {
        if (! is_scalar($v)) {
            return null;
        }

        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }

    private function bool(mixed $v): bool
    {
        if (is_bool($v)) {
            return $v;
        }

        if (is_numeric($v)) {
            return (float) $v > 0;
        }

        return in_array(strtolower(trim((string) $v)), ['true', 'yes', 'y', '1'], true);
    }

    private function date(mixed $v): ?Carbon
    {
        if (! is_scalar($v) || trim((string) $v) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $v);
        } catch (\Throwable) {
            return null;
        }
    }
}
