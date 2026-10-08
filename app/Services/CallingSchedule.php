<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Carbon;

/**
 * Whether an agent may call right now, and when it next may.
 *
 * One place answers both questions, because instant leads and campaign dispatch
 * both need them and must not disagree -- a lead held back by the window has to
 * be released at exactly the moment a campaign would resume.
 *
 * Times are read in the agent's own timezone, falling back to the workspace's.
 * Comparing a client's calling hours in server time is how an agent ends up
 * ringing people at six in the morning.
 */
class CallingSchedule
{
    /** Default window for an agent that has not set one. */
    private const DEFAULT_START = '09:00';
    private const DEFAULT_END   = '20:00';

    public function __construct(private readonly Agent $agent)
    {
    }

    public static function for(Agent $agent): self
    {
        return new self($agent);
    }

    public function timezone(): string
    {
        return $this->agent->timezone
            ?: $this->agent->workspace?->timezone
            ?: config('app.timezone', 'Asia/Kolkata');
    }

    /** True when the agent is allowed to place a call at this moment. */
    public function isOpen(?Carbon $at = null): bool
    {
        if ($this->agent->is_always_on) {
            return true;
        }

        $at = ($at ?? now())->copy()->setTimezone($this->timezone());

        if (! $this->isAllowedDay($at)) {
            return false;
        }

        [$start, $end] = $this->window($at);

        return $at->betweenIncluded($start, $end);
    }

    /**
     * The next moment the agent may call.
     *
     * Returns now when the window is already open, so a caller can use it
     * unconditionally as a delay-until value.
     */
    public function nextOpening(?Carbon $at = null): Carbon
    {
        $at = ($at ?? now())->copy()->setTimezone($this->timezone());

        if ($this->agent->is_always_on || $this->isOpen($at)) {
            return $at;
        }

        // Walk forward a day at a time. A week is enough: an agent with no
        // allowed days at all would otherwise loop, so that is guarded below.
        $candidate = $at->copy();

        for ($i = 0; $i < 8; $i++) {
            [$start, $end] = $this->window($candidate);

            if ($this->isAllowedDay($candidate) && $candidate->lessThan($end)) {
                return $candidate->greaterThan($start) ? $candidate : $start;
            }

            $candidate = $candidate->copy()->addDay()->startOfDay();
        }

        // No day is allowed. Holding the lead for a week is wrong, so it goes
        // out at the next window start and the misconfiguration is visible.
        return $at->copy()->addDay();
    }

    /** Seconds to wait before this agent may call, zero when it may now. */
    public function delaySeconds(?Carbon $at = null): int
    {
        $at   = ($at ?? now())->copy()->setTimezone($this->timezone());
        $next = $this->nextOpening($at);

        return max(0, $at->diffInSeconds($next, false));
    }

    /** Plain wording for the agent page. */
    public function describe(): string
    {
        if ($this->agent->is_always_on) {
            return 'Always on, 24 hours a day';
        }

        $days = $this->days();
        $all  = count($days) === 7;

        return sprintf(
            '%s to %s, %s (%s)',
            $this->agent->calling_window_start ?: self::DEFAULT_START,
            $this->agent->calling_window_end ?: self::DEFAULT_END,
            $all ? 'every day' : implode(', ', $days),
            $this->timezone(),
        );
    }

    /** @return list<string> */
    public function days(): array
    {
        $days = (array) ($this->agent->calling_days ?? []);

        return $days !== []
            ? array_values($days)
            : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    }

    // ---------------------------------------------------------------

    private function isAllowedDay(Carbon $at): bool
    {
        return in_array($at->format('l'), $this->days(), true);
    }

    /**
     * The window on the given day.
     *
     * An end before the start means the window crosses midnight, so the end
     * moves to the next day rather than producing an empty range.
     *
     * @return array{0:Carbon,1:Carbon}
     */
    private function window(Carbon $at): array
    {
        $tz    = $this->timezone();
        $start = Carbon::parse($at->toDateString() . ' ' . ($this->agent->calling_window_start ?: self::DEFAULT_START), $tz);
        $end   = Carbon::parse($at->toDateString() . ' ' . ($this->agent->calling_window_end ?: self::DEFAULT_END), $tz);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }
}
