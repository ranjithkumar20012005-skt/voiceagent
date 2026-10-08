<?php

namespace App\Services\Presenters;

use App\Models\CallAttempt;
use App\Support\CallStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Everything the result and conversation pages show about one call, in a form a
 * client can read.
 *
 * Two rules shape this class:
 *
 *   - Nothing is invented. There is no local summary generator: `summary()`
 *     returns what the platform sent or null, and the page then says the summary
 *     is unavailable rather than making one up.
 *   - No raw provider payload reaches a view. `capturedFields()` turns the
 *     agent's output variables into labelled, formatted values and drops the
 *     plumbing keys that only matter to us.
 */
class CallResultPresenter
{
    /**
     * Output variables that drive our own columns rather than being information
     * the client wants listed back to them.
     */
    private const INTERNAL_KEYS = [
        'call_disposition', 'lead_generated', 'callback_required', 'callback_at',
        'customer_id', 'workspace_id', 'app_id', 'app_version', 'deployment_id',
        'agent_id', 'attempt_id', 'interaction_id',
    ];

    public function __construct(private readonly CallAttempt $call)
    {
    }

    public static function for(CallAttempt $call): self
    {
        return new self($call);
    }

    // ---------------------------------------------------------------
    // Headline
    // ---------------------------------------------------------------

    public function customerName(): string
    {
        return $this->call->customer?->name ?: 'Unknown customer';
    }

    public function agentName(): string
    {
        return $this->call->agent?->name ?: 'Your agent';
    }

    public function phone(): string
    {
        $number = $this->call->customer_phone_number ?: $this->call->customer?->phone_number;

        return $number ? \App\Support\PhoneNumber::display($number) : 'Not recorded';
    }

    public function directionLabel(): string
    {
        return $this->call->direction === 'inbound' ? 'Inbound' : 'Outbound';
    }

    public function languageLabel(): ?string
    {
        return $this->call->language ?: null;
    }

    /** Human duration, or null when the call never connected. */
    public function duration(): ?string
    {
        $seconds = (int) $this->call->duration_seconds;

        if ($seconds <= 0) {
            return null;
        }

        return $seconds < 60
            ? "{$seconds}s"
            : intdiv($seconds, 60) . 'm ' . str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT) . 's';
    }

    public function startedAt(): ?Carbon
    {
        return $this->call->started_at ?? $this->call->created_at;
    }

    // ---------------------------------------------------------------
    // Result
    // ---------------------------------------------------------------

    /**
     * The call status in the words a client uses.
     *
     * "Connected" is telephony vocabulary; a business asks whether the call was
     * answered. The underlying values are unchanged -- this is wording only.
     */
    public function callStatusLabel(): string
    {
        return match ($this->call->connectivity_status) {
            CallStatus::CONNECTED   => 'Answered',
            CallStatus::NO_ANSWER   => 'No answer',
            CallStatus::BUSY        => 'Busy',
            CallStatus::CONN_FAILED => 'Failed',
            default                 => $this->call->connectivity_label,
        };
    }

    public function outcomeLabel(): string
    {
        return $this->call->outcome_label;
    }

    /** Interested / Not interested / Unknown, as a plain reading of the outcome. */
    public function leadStatusLabel(): string
    {
        return match ($this->call->call_disposition) {
            CallStatus::INTERESTED     => 'Interested',
            CallStatus::NOT_INTERESTED => 'Not interested',
            CallStatus::CALLBACK       => 'Callback requested',
            CallStatus::DO_NOT_CALL    => 'Do not call',
            default                    => $this->call->lead_generated ? 'Interested' : 'Not established',
        };
    }

    public function isInterested(): bool
    {
        return $this->call->call_disposition === CallStatus::INTERESTED || (bool) $this->call->lead_generated;
    }

    public function callbackRequired(): bool
    {
        return (bool) $this->call->callback_required || $this->call->callback_at !== null;
    }

    public function callbackAt(): ?Carbon
    {
        return $this->call->callback_at;
    }

    // ---------------------------------------------------------------
    // Summary
    // ---------------------------------------------------------------

    /**
     * The platform's own summary, or null.
     *
     * Deliberately never generated locally: a made-up summary of a real customer
     * conversation would be worse than none.
     */
    public function summary(): ?string
    {
        $summary = $this->call->summary;

        if (is_string($summary) && trim($summary) !== '') {
            return trim($summary);
        }

        // Some agents report their own notes as an output variable instead.
        $notes = $this->variables()['customer_notes'] ?? null;

        return is_string($notes) && trim($notes) !== '' ? trim($notes) : null;
    }

    public function summaryFallback(): string
    {
        return 'Summary not available for this call.';
    }

    // ---------------------------------------------------------------
    // Captured information
    // ---------------------------------------------------------------

    /**
     * What the agent captured, as label/value pairs ready to print.
     *
     * @return Collection<int,array{label:string,value:string}>
     */
    public function capturedFields(): Collection
    {
        return collect($this->variables())
            ->reject(fn ($value, $key) => in_array((string) $key, self::INTERNAL_KEYS, true))
            ->map(fn ($value, $key) => ['label' => $this->label((string) $key), 'value' => $this->value($value)])
            ->reject(fn (array $row) => $row['value'] === null)
            ->sortBy('label')
            ->values();
    }

    public function hasCapturedFields(): bool
    {
        return $this->capturedFields()->isNotEmpty();
    }

    /** @return array<string,mixed> */
    private function variables(): array
    {
        return array_merge(
            (array) ($this->call->final_agent_variables ?? []),
            (array) ($this->call->output_agent_variables ?? []),
        );
    }

    private function label(string $key): string
    {
        return Str::of($key)->replace(['_', '-'], ' ')->squish()->title()->value();
    }

    /** Formats a value for display, or null when there is nothing worth showing. */
    private function value(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            // Flattened to a readable list rather than printed as JSON.
            $flat = collect($value)
                ->flatten()
                ->filter(fn ($v) => is_scalar($v) && trim((string) $v) !== '')
                ->map(fn ($v) => trim((string) $v));

            return $flat->isEmpty() ? null : $flat->implode(', ');
        }

        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        // A date is far more readable than the timestamp the agent reported.
        if (preg_match('/^\d{4}-\d{2}-\d{2}([T ]\d{2}:\d{2})?/', $text)) {
            try {
                return Carbon::parse($text)->format('j M Y, g:i a');
            } catch (\Throwable) {
                // Not a date after all; fall through and print it as given.
            }
        }

        return Str::limit($text, 400);
    }
}
