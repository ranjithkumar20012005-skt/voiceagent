<?php

namespace App\Support;

/**
 * Canonical vocabulary for the two *separate* concepts the dashboard shows:
 *
 *   1. Connectivity  -- did the phone call physically connect?
 *   2. Outcome       -- what did the conversation achieve (business disposition)?
 *
 * Both are resolved through config/sarvam.php maps so a client can retune the
 * mapping without touching code.
 */
final class CallStatus
{
    // ----- lifecycle of a local call_attempt row -----
    public const QUEUED      = 'queued';
    public const DISPATCHED  = 'dispatched';
    public const COMPLETED   = 'completed';
    public const FAILED      = 'failed';

    // ----- connectivity -----
    public const CONNECTED = 'connected';
    public const NO_ANSWER = 'no_answer';
    public const BUSY      = 'busy';
    public const CONN_FAILED = 'failed';

    // ----- business outcome -----
    public const INTERESTED      = 'interested';
    public const CALLBACK        = 'callback';
    public const NOT_INTERESTED  = 'not_interested';
    public const ALREADY_RENEWED = 'already_renewed';
    public const WRONG_PERSON    = 'wrong_person';
    public const DO_NOT_CALL     = 'do_not_call';
    public const ESCALATED       = 'escalated';
    public const UNKNOWN         = 'unknown';

    /** @return array<string,string> */
    public static function connectivityLabels(): array
    {
        return [
            self::CONNECTED   => 'Connected',
            self::NO_ANSWER   => 'No Answer',
            self::BUSY        => 'Busy',
            self::CONN_FAILED => 'Failed',
        ];
    }

    /** @return array<string,string> */
    public static function outcomeLabels(): array
    {
        return [
            self::INTERESTED      => 'Interested',
            self::CALLBACK        => 'Callback',
            self::NOT_INTERESTED  => 'Not Interested',
            self::ALREADY_RENEWED => 'Already Renewed',
            self::WRONG_PERSON    => 'Wrong Person',
            self::DO_NOT_CALL     => 'Do Not Call',
            self::ESCALATED       => 'Escalated',
            self::UNKNOWN         => 'Unknown',
        ];
    }

    /**
     * Map a raw Sarvam connectivity/status string onto our vocabulary.
     * Unrecognised values return null rather than guessing.
     */
    public static function connectivityFrom(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $key = strtolower(trim($raw));

        return config('sarvam.connectivity_map')[$key] ?? null;
    }

    /**
     * Map the agent's `call_disposition` output variable onto a business
     * outcome. Anything unrecognised becomes UNKNOWN -- never a negative
     * outcome, because an unclear call is not a rejection.
     */
    public static function outcomeFrom(?string $raw): string
    {
        if ($raw === null || $raw === '') {
            return self::UNKNOWN;
        }

        $key = strtolower(trim(str_replace([' ', '-'], '_', $raw)));

        return config('sarvam.disposition_map')[$key] ?? self::UNKNOWN;
    }

    /** Bootstrap badge class for a connectivity value. */
    public static function connectivityBadge(?string $value): string
    {
        return match ($value) {
            self::CONNECTED   => 'badge-green',
            self::NO_ANSWER   => 'badge-orange',
            self::BUSY        => 'badge-purple',
            self::CONN_FAILED => 'badge-red',
            default           => 'badge-gray',
        };
    }

    /** Bootstrap badge class for an outcome value. */
    public static function outcomeBadge(?string $value): string
    {
        return match ($value) {
            self::INTERESTED      => 'badge-green',
            self::CALLBACK        => 'badge-cyan',
            self::ALREADY_RENEWED => 'badge-purple',
            self::NOT_INTERESTED  => 'badge-red',
            self::WRONG_PERSON    => 'badge-orange',
            self::DO_NOT_CALL     => 'badge-red',
            self::ESCALATED       => 'badge-orange',
            default               => 'badge-gray',
        };
    }
}
