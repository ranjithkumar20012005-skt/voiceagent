<?php

namespace App\Services;

use RuntimeException;

/**
 * Raised for any Voice Agents API failure. The message is safe to log; it
 * never contains the API key.
 */
class SarvamException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }

    /**
     * A distinct, actionable message per upstream status.
     *
     * Deliberately NOT a catch-all "authentication failed" -- each status has a
     * different remedy, and mislabelling them sends the operator hunting for
     * the wrong problem.
     */
    public function userMessage(): string
    {
        return match (true) {
            $this->status === 401 => 'Invalid or missing Sarvam API key.',
            $this->status === 403 => 'API key does not have access to this organisation/workspace.',
            $this->status === 402 => 'Sarvam account has insufficient credits. Top up to resume outbound calls.',
            $this->status === 404 => 'Configured Sarvam resource was not found.',
            $this->status === 422 => 'Voice agent configuration is invalid. Check agent version, connection ID, caller number, or required variables.',
            $this->status === 429 => 'Sarvam rate limit or usage limit reached.',
            $this->status !== null && $this->status >= 500 && $this->status <= 599 => 'Sarvam service is temporarily unavailable.',
            $this->status === null => 'Could not reach the voice service. Please retry.',
            default => 'The voice service rejected the request (HTTP ' . $this->status . ').',
        };
    }

    /**
     * The HTTP status this application should return to the browser.
     *
     * A misconfiguration on our side is not a "bad gateway" -- only a genuine
     * upstream outage or unreachable host is.
     */
    public function responseStatus(): int
    {
        return match (true) {
            // Our credentials/config are wrong: the operator must fix the
            // server, so report a server-side error rather than a gateway one.
            in_array($this->status, [401, 403, 404], true) => 500,
            // Billing: nothing to retry, the operator must top up.
            $this->status === 402 => 402,
            // The payload we built was rejected.
            $this->status === 422 => 422,
            $this->status === 429 => 429,
            // Genuine upstream failure or unreachable host.
            default => 502,
        };
    }

    /** Detail for server-side logs and (in debug builds) the operator. */
    public function upstreamDetail(): string
    {
        return $this->getMessage();
    }
}
