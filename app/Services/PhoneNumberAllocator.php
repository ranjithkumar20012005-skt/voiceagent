<?php

namespace App\Services;

use App\Contracts\PhoneNumberProviderInterface;
use App\Models\Agent;
use App\Models\PhoneNumber;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Hands numbers out of the shared pool.
 *
 * The whole point of this class is the lock. Two customers clicking "claim" on
 * the same number at the same moment would otherwise both read it as available
 * and both write their own workspace id, and the loser would silently be calling
 * from a number someone else owns. The row is therefore selected
 * FOR UPDATE inside a transaction and re-checked after the lock is held.
 */
class PhoneNumberAllocator
{
    public function __construct(private readonly PhoneNumberProviderInterface $numbers)
    {
    }

    /**
     * Claim a number from the pool for a workspace.
     *
     * @throws NumberUnavailableException when another workspace got there first
     */
    public function claim(PhoneNumber|int $number, Workspace $workspace): PhoneNumber
    {
        $id = $number instanceof PhoneNumber ? $number->id : $number;

        return DB::transaction(function () use ($id, $workspace) {
            $locked = PhoneNumber::query()->whereKey($id)->lockForUpdate()->first();

            if (! $locked) {
                throw new NumberUnavailableException('Phone number is currently unavailable.');
            }

            // Re-checked *after* the lock: the state we read before waiting for
            // it may already be stale.
            if ($locked->workspace_id !== null && (int) $locked->workspace_id !== (int) $workspace->id) {
                throw new NumberUnavailableException('Phone number is currently unavailable.');
            }

            if ($locked->workspace_id === null && $locked->status !== PhoneNumber::AVAILABLE) {
                throw new NumberUnavailableException('Phone number is currently unavailable.');
            }

            $locked->forceFill([
                'workspace_id' => $workspace->id,
                'status'       => PhoneNumber::ASSIGNED,
                'assigned_at'  => now(),
            ])->save();

            return $locked;
        });
    }

    /**
     * Attach a claimed number to one of the workspace's agents.
     *
     * Local state is committed first and the provider mapping attempted after:
     * a provider hiccup must not leave the number half-claimed, and the mapping
     * is reconcilable from GET /deployments afterwards.
     *
     * @return array{number:PhoneNumber,provider_synced:bool}
     */
    public function attachToAgent(PhoneNumber $number, Agent $agent): array
    {
        if ((int) $number->workspace_id !== (int) $agent->workspace_id) {
            throw new NumberUnavailableException('Phone number is currently unavailable.');
        }

        DB::transaction(function () use ($number, $agent) {
            $locked = PhoneNumber::query()->whereKey($number->id)->lockForUpdate()->first();

            if (! $locked || (int) $locked->workspace_id !== (int) $agent->workspace_id) {
                throw new NumberUnavailableException('Phone number is currently unavailable.');
            }

            // One number, one agent: free whatever else in this workspace held it.
            PhoneNumber::query()
                ->where('workspace_id', $agent->workspace_id)
                ->where('assigned_agent_id', $agent->id)
                ->whereKeyNot($locked->id)
                ->update(['assigned_agent_id' => null, 'status' => PhoneNumber::ASSIGNED]);

            $locked->forceFill([
                'assigned_agent_id' => $agent->id,
                'status'            => PhoneNumber::ACTIVE,
            ])->save();

            $agent->forceFill(['assigned_phone_number_id' => $locked->id])->save();
        });

        $synced = false;

        try {
            $synced = $this->numbers->assignNumber($number->refresh(), $agent);
        } catch (\Throwable $e) {
            // Local assignment stands; the mapping is retried by reconciliation.
            Log::warning('voice.number.provider_sync_deferred', [
                'workspace_id' => $agent->workspace_id,
                'agent_id'     => $agent->id,
                'action'       => 'assign_number',
                'message'      => $e->getMessage(),
            ]);
        }

        return ['number' => $number->refresh(), 'provider_synced' => $synced];
    }

    /** Return a number to the pool. */
    public function release(PhoneNumber $number): PhoneNumber
    {
        try {
            $this->numbers->releaseNumber($number);
        } catch (\Throwable $e) {
            Log::warning('voice.number.provider_release_deferred', [
                'workspace_id' => $number->workspace_id,
                'action'       => 'release_number',
                'message'      => $e->getMessage(),
            ]);
        }

        return DB::transaction(function () use ($number) {
            $locked = PhoneNumber::query()->whereKey($number->id)->lockForUpdate()->first();

            if ($locked->assigned_agent_id) {
                Agent::withoutGlobalScope('workspace')
                    ->whereKey($locked->assigned_agent_id)
                    ->update(['assigned_phone_number_id' => null]);
            }

            $locked->forceFill([
                'workspace_id'      => null,
                'assigned_agent_id' => null,
                'assigned_at'       => null,
                'status'            => PhoneNumber::AVAILABLE,
            ])->save();

            return $locked;
        });
    }
}
