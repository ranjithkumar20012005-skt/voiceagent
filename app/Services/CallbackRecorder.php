<?php

namespace App\Services;

use App\Models\Callback;
use App\Models\CallAttempt;

/**
 * Turns "call me back on Saturday" into a record a worker can act on.
 *
 * Keyed on the call, with a unique index behind it, so the provider retrying a
 * delivery updates the one callback instead of stacking up duplicates. A call
 * that no longer asks for a callback has its existing one cancelled rather than
 * deleted, because the client should still be able to see that it was asked for
 * and then withdrawn.
 */
class CallbackRecorder
{
    public function record(CallAttempt $call): ?Callback
    {
        $existing = Callback::withoutGlobalScope('workspace')->where('call_id', $call->id)->first();

        if (! $this->isWanted($call)) {
            // Asked for earlier, not any more.
            if ($existing && in_array($existing->status, [Callback::SCHEDULED, Callback::DUE], true)) {
                $existing->forceFill(['status' => Callback::CANCELLED])->save();
            }

            return $existing;
        }

        // Without a customer there is nobody to call back.
        if (! $call->customer_id) {
            return null;
        }

        $scheduledAt = $call->callback_at ?? $this->defaultTime($call);

        $attributes = [
            'workspace_id' => $call->workspace_id,
            'agent_id'     => $call->agent_id,
            'customer_id'  => $call->customer_id,
            'scheduled_at' => $scheduledAt,
            'timezone'     => config('app.timezone', 'Asia/Kolkata'),
            'reason'       => $this->reason($call),
        ];

        if ($existing) {
            // A completed or cancelled callback is history: a replayed webhook
            // must not quietly reopen it.
            if (in_array($existing->status, [Callback::COMPLETED, Callback::CANCELLED], true)) {
                return $existing;
            }

            $existing->forceFill($attributes + ['status' => $this->statusFor($scheduledAt)])->save();

            return $existing;
        }

        $callback = new Callback($attributes + ['call_id' => $call->id, 'status' => $this->statusFor($scheduledAt)]);
        $callback->workspace_id = $call->workspace_id;
        $callback->save();

        return $callback;
    }

    private function isWanted(CallAttempt $call): bool
    {
        return (bool) $call->callback_required || $call->callback_at !== null;
    }

    /**
     * When the agent said a callback is needed but gave no time, the request is
     * still recorded -- as due now, so it surfaces rather than being lost.
     */
    private function defaultTime(CallAttempt $call): \Illuminate\Support\Carbon
    {
        return $call->ended_at ?? $call->created_at ?? now();
    }

    private function statusFor(\DateTimeInterface $scheduledAt): string
    {
        return $scheduledAt <= now() ? Callback::DUE : Callback::SCHEDULED;
    }

    /** Whatever the agent gave as a reason, if anything. */
    private function reason(CallAttempt $call): ?string
    {
        $vars = array_merge(
            (array) ($call->final_agent_variables ?? []),
            (array) ($call->output_agent_variables ?? []),
        );

        foreach (['callback_reason', 'reason', 'customer_notes'] as $key) {
            $value = $vars[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return mb_substr(trim($value), 0, 500);
            }
        }

        return null;
    }
}
