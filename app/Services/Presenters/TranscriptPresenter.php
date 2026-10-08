<?php

namespace App\Services\Presenters;

use App\Models\CallAttempt;
use Illuminate\Support\Collection;

/**
 * Turns a stored transcript into readable turns.
 *
 * All transcript parsing lives here. The shape varies -- different payload
 * versions use different keys for the speaker and the text, some turns carry a
 * translation as well as the original, and older rows may hold a bare list of
 * strings -- and none of that belongs in a Blade file. A view asks for
 * `turns()` and gets speaker, text and nothing else.
 *
 * Nothing is invented: a turn with no text is dropped, and a transcript that
 * cannot be read at all comes back empty so the page can say so.
 */
class TranscriptPresenter
{
    public const AGENT    = 'agent';
    public const CUSTOMER = 'customer';

    /** Keys seen carrying the spoken text, in the order we prefer them. */
    private const TEXT_KEYS = ['en_text', 'text', 'content', 'message', 'transcript', 'value'];

    /** Keys seen carrying the speaker. */
    private const ROLE_KEYS = ['role', 'speaker', 'from', 'participant', 'author'];

    /** Keys carrying the original-language text when the main text is a translation. */
    private const ORIGINAL_KEYS = ['text', 'original_text', 'native_text', 'raw_text'];

    public function __construct(private readonly CallAttempt $call)
    {
    }

    public static function for(CallAttempt $call): self
    {
        return new self($call);
    }

    /**
     * The conversation, in order.
     *
     * @return Collection<int,array{speaker:string,label:string,text:string,original:?string,is_agent:bool}>
     */
    public function turns(): Collection
    {
        $raw = $this->call->transcript;

        if (! is_array($raw) || $raw === []) {
            return collect();
        }

        // Some payloads wrap the list; unwrap one level before giving up.
        if (! array_is_list($raw)) {
            $raw = $raw['turns'] ?? $raw['messages'] ?? $raw['transcript'] ?? [];
        }

        return collect(is_array($raw) ? $raw : [])
            ->map(fn ($turn, $i) => $this->normaliseTurn($turn, $i))
            ->filter()
            ->values();
    }

    public function hasTranscript(): bool
    {
        return $this->turns()->isNotEmpty();
    }

    /**
     * True when there is a transcript but the call is not finished, so what we
     * have is only part of the conversation.
     */
    public function isPartial(): bool
    {
        return $this->hasTranscript() && $this->call->ended_at === null;
    }

    public function agentLabel(): string
    {
        return $this->call->agent?->name ?: 'Agent';
    }

    public function customerLabel(): string
    {
        return $this->call->customer?->name ?: 'Customer';
    }

    /** @return array{speaker:string,label:string,text:string,original:?string,is_agent:bool}|null */
    private function normaliseTurn(mixed $turn, int $index): ?array
    {
        // A legacy row may hold plain strings. With no speaker recorded, speakers
        // are assumed to alternate from the agent, who always opens the call.
        if (is_string($turn)) {
            $text = trim($turn);

            return $text === '' ? null : $this->turn($index % 2 === 0 ? self::AGENT : self::CUSTOMER, $text, null);
        }

        if (! is_array($turn)) {
            return null;
        }

        $text = $this->firstString($turn, self::TEXT_KEYS);

        // No words means nothing to show, however much metadata came with it.
        if ($text === null) {
            return null;
        }

        $speaker = $this->speaker($turn, $index);

        // Keep the original utterance only when it differs from what we display,
        // so a bilingual transcript does not print every line twice.
        $original = $this->firstString($turn, self::ORIGINAL_KEYS);
        $original = ($original !== null && $original !== $text) ? $original : null;

        return $this->turn($speaker, $text, $original);
    }

    private function speaker(array $turn, int $index): string
    {
        $role = $this->firstString($turn, self::ROLE_KEYS);

        if ($role === null) {
            // An empty speaker label is common in partial transcripts; alternating
            // from the agent is the best honest reading of the order.
            return $index % 2 === 0 ? self::AGENT : self::CUSTOMER;
        }

        $role = strtolower($role);

        return match (true) {
            str_contains($role, 'agent'), str_contains($role, 'assistant'),
            str_contains($role, 'bot'), str_contains($role, 'system') => self::AGENT,
            default => self::CUSTOMER,
        };
    }

    /** @return array{speaker:string,label:string,text:string,original:?string,is_agent:bool} */
    private function turn(string $speaker, string $text, ?string $original): array
    {
        $isAgent = $speaker === self::AGENT;

        return [
            'speaker'  => $speaker,
            'label'    => $isAgent ? $this->agentLabel() : $this->customerLabel(),
            'text'     => $text,
            'original' => $original,
            'is_agent' => $isAgent,
        ];
    }

    /** @param list<string> $keys */
    private function firstString(array $turn, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $turn[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
