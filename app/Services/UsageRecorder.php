<?php

namespace App\Services;

use App\Models\CallAttempt;
use App\Models\UsageRecord;

/**
 * Meters one call against its workspace.
 *
 * Every client shares our single provider account, so the provider's own totals
 * cannot say who spent what -- this table is the only record of that.
 *
 * Exactly one row per call, enforced by a unique index on call_id, so a replayed
 * webhook rewrites that row instead of billing the client twice. Money is decimal
 * throughout and computed in paise/cents as integers before being divided, so
 * repeated rounding cannot drift.
 */
class UsageRecorder
{
    public function record(CallAttempt $call): ?UsageRecord
    {
        // Nothing was spoken, so nothing is metered. A row of zeroes would only
        // make the client's usage page look busier than their account was.
        $seconds = max(0, (int) $call->duration_seconds);

        if ($seconds <= 0 || ! $call->workspace_id) {
            return null;
        }

        $minimum = max(1, (int) config('voice.billing.minimum_billable_seconds', 1));
        $billable = max($minimum, $seconds);

        // Charged per started minute, which is how the client is quoted.
        $minutes = (int) ceil($billable / 60);
        $rate    = (string) config('voice.billing.customer_rate_per_minute', '0');

        $existing = UsageRecord::withoutGlobalScope('workspace')->where('call_id', $call->id)->first();

        $attributes = [
            'workspace_id'     => $call->workspace_id,
            'agent_id'         => $call->agent_id,
            'campaign_id'      => null,
            'duration_seconds' => $seconds,
            'billable_minutes' => number_format($minutes, 2, '.', ''),
            'provider'         => config('voice.provider', 'sarvam'),
            'customer_cost'    => $this->money($rate, $minutes),
            'currency'         => config('voice.billing.currency', 'INR'),
        ];

        if ($existing) {
            $existing->forceFill($attributes)->save();

            return $existing;
        }

        $record = new UsageRecord($attributes + ['call_id' => $call->id]);
        $record->workspace_id = $call->workspace_id;
        $record->save();

        return $record;
    }

    /**
     * rate x minutes, in minor units, so the arithmetic is integer and the
     * result is exact. Floating point has no place in a billable figure.
     */
    private function money(string $ratePerMinute, int $minutes): string
    {
        $minor = (int) round(((float) $ratePerMinute) * 100);

        return number_format(($minor * $minutes) / 100, 4, '.', '');
    }
}
