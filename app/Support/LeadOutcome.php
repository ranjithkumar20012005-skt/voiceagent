<?php

namespace App\Support;

use App\Models\CallAttempt;

/**
 * Client-facing vocabulary.
 *
 * CallStatus holds the canonical internal values that the webhook pipeline
 * writes. This class is the *presentation* layer on top of them: plain business
 * wording a non-technical client understands, plus the single definition of
 * what counts as a "hot lead".
 *
 * Nothing here changes stored data -- it only relabels it.
 */
final class LeadOutcome
{
    /**
     * Canonical outcome => the words the client sees.
     *
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            CallStatus::INTERESTED      => 'Interested',
            CallStatus::CALLBACK        => 'Follow-up',
            CallStatus::NOT_INTERESTED  => 'Not Interested',
            CallStatus::ALREADY_RENEWED => 'Already Completed',
            CallStatus::WRONG_PERSON    => 'Wrong Person',
            CallStatus::DO_NOT_CALL     => 'Do Not Contact',
            CallStatus::ESCALATED       => 'Needs Attention',
            CallStatus::UNKNOWN         => 'No Outcome',
        ];
    }

    /** Connectivity, in client wording. */
    public static function connectivityLabels(): array
    {
        return [
            CallStatus::CONNECTED   => 'Answered',
            CallStatus::NO_ANSWER   => 'No Answer',
            CallStatus::BUSY        => 'Busy',
            CallStatus::CONN_FAILED => 'Not Reachable',
        ];
    }

    public static function label(?string $outcome): string
    {
        return self::labels()[$outcome] ?? 'No Outcome';
    }

    public static function connectivityLabel(CallAttempt $attempt): string
    {
        if ($value = $attempt->connectivity_status) {
            return self::connectivityLabels()[$value] ?? 'Unavailable';
        }

        return match ($attempt->status) {
            CallStatus::QUEUED     => 'Queued',
            CallStatus::DISPATCHED => 'Calling',
            CallStatus::FAILED     => 'Not Reachable',
            default                => 'Unavailable',
        };
    }

    /**
     * Outcomes worth surfacing on the Overview "Calls Overview" block. The
     * long tail stays available on the Leads page rather than crowding the
     * summary.
     *
     * @return list<string>
     */
    public static function headlineOutcomes(): array
    {
        return [CallStatus::INTERESTED, CallStatus::CALLBACK, CallStatus::NOT_INTERESTED];
    }

    /**
     * A hot lead is decided by the agent's *structured* output -- either the
     * lead_generated flag or an `interested` disposition. We never infer one
     * from transcript text.
     */
    public static function isHot(CallAttempt $attempt): bool
    {
        return (bool) $attempt->lead_generated
            || $attempt->call_disposition === CallStatus::INTERESTED;
    }

    /** Short headline for a hot-lead card, e.g. "Interested". */
    public static function hotHeadline(CallAttempt $attempt): string
    {
        if ($attempt->call_disposition && $attempt->call_disposition !== CallStatus::UNKNOWN) {
            return self::label($attempt->call_disposition);
        }

        return $attempt->lead_generated ? 'Qualified Lead' : 'No Outcome';
    }

    /** Badge class for an outcome, in the client palette. */
    public static function badge(?string $outcome): string
    {
        return match ($outcome) {
            CallStatus::INTERESTED      => 'badge-green',
            CallStatus::CALLBACK        => 'badge-cyan',
            CallStatus::ALREADY_RENEWED => 'badge-purple',
            CallStatus::NOT_INTERESTED  => 'badge-red',
            CallStatus::WRONG_PERSON,
            CallStatus::ESCALATED       => 'badge-orange',
            CallStatus::DO_NOT_CALL     => 'badge-red',
            default                     => 'badge-gray',
        };
    }

    public static function connectivityBadge(CallAttempt $attempt): string
    {
        return match ($attempt->connectivity_status) {
            CallStatus::CONNECTED   => 'badge-green',
            CallStatus::NO_ANSWER   => 'badge-orange',
            CallStatus::BUSY        => 'badge-purple',
            CallStatus::CONN_FAILED => 'badge-red',
            default                 => $attempt->status === CallStatus::FAILED ? 'badge-red' : 'badge-gray',
        };
    }
}
