<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Support\CallStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    use BelongsToWorkspace;
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'agent_id', 'import_batch_id',
        'name', 'enabled', 'frequency', 'run_at', 'run_days', 'timezone',
        'expiry_within_days', 'customer_statuses', 'skip_do_not_call',
        'skip_already_renewed', 'skip_active_callback', 'only_unreached',
        'min_days_between_calls',
        'max_calls_per_run', 'max_retries', 'window_start', 'window_end',
        'attempts_per_second', 'last_run_at', 'last_run_status',
        'last_run_message', 'last_run_count',
    ];

    protected function casts(): array
    {
        return [
            'enabled'              => 'boolean',
            'skip_do_not_call'     => 'boolean',
            'skip_already_renewed' => 'boolean',
            'skip_active_callback' => 'boolean',
            'customer_statuses'    => 'array',
            'only_unreached'       => 'boolean',
            'run_days'             => 'array',
            'last_run_at'          => 'datetime',
            'expiry_within_days'   => 'integer',
            'max_calls_per_run'    => 'integer',
            'last_run_count'       => 'integer',
        ];
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Customers this automation would call right now.
     *
     * Every exclusion is explicit and conservative: do-not-call is always
     * honoured, and a customer with a callback scheduled in the future is left
     * alone so we do not ring them ahead of their own appointment.
     */
    public function eligibleCustomers(): Builder
    {
        $q = Customer::query()->callable();

        if ($this->expiry_within_days > 0) {
            $q->whereNotNull('policy_expiry_date')
              ->whereDate('policy_expiry_date', '>=', now()->toDateString())
              ->whereDate('policy_expiry_date', '<=', now()->addDays($this->expiry_within_days)->toDateString());
        }

        $statuses = array_filter((array) $this->customer_statuses);
        if ($statuses) {
            $q->whereIn('customer_status', $statuses);
        }

        if ($this->skip_already_renewed) {
            $q->where(function (Builder $w) {
                $w->whereNull('last_outcome')
                  ->orWhereNotIn('last_outcome', [CallStatus::ALREADY_RENEWED, CallStatus::DO_NOT_CALL]);
            });
        }

        if ($this->skip_active_callback) {
            $q->where(function (Builder $w) {
                $w->whereNull('next_callback_at')
                  ->orWhere('next_callback_at', '<=', now());
            });
        }

        if ($this->min_days_between_calls > 0) {
            $q->where(function (Builder $w) {
                $w->whereNull('last_call_at')
                  ->orWhere('last_call_at', '<=', now()->subDays($this->min_days_between_calls));
            });
        }

        // Work one uploaded list rather than every customer, so two schedules
        // on the same workspace do not call into each other's leads.
        if ($this->import_batch_id) {
            $q->where('import_batch_id', $this->import_batch_id);
        }

        // Follow-up mode: only the people the last run could not actually speak
        // to. Someone already reached is left alone, however they answered.
        if ($this->only_unreached) {
            $q->where(function (Builder $w) {
                $w->whereNull('last_connectivity')
                  ->orWhereIn('last_connectivity', [CallStatus::NO_ANSWER, CallStatus::BUSY, CallStatus::CONN_FAILED]);
            });

            // And stop after the configured number of attempts, so an
            // unreachable number is not dialled forever.
            if ($this->max_retries > 0) {
                $q->where('call_count', '<=', $this->max_retries);
            }
        }

        // Insurance lists are worked by expiry; a generic list has no such date,
        // and ordering by a null column would make the run order arbitrary.
        return $this->expiry_within_days > 0
            ? $q->orderBy('policy_expiry_date')->orderBy('id')
            : $q->orderBy('id');
    }

    /** The agent that places this schedule's calls. */
    public function agent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function importBatch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    /** True when today is a day this schedule is allowed to run. */
    public function runsToday(?\DateTimeInterface $at = null): bool
    {
        $days = array_filter((array) $this->run_days);

        if ($days === []) {
            return true;
        }

        $now = $at
            ? \Illuminate\Support\Carbon::instance($at)->setTimezone($this->timezone)
            : now($this->timezone);

        return in_array($now->format('l'), $days, true);
    }

    /**
     * Whether this schedule is due now.
     *
     * Due means: enabled, today is an allowed day, the run time has passed, the
     * calling window is open, and it has not already run today. The last check
     * is what stops a scheduler ticking every minute from dialling the list
     * sixty times an hour.
     */
    public function isDue(?\DateTimeInterface $at = null): bool
    {
        if (! $this->enabled || ! $this->runsToday($at) || ! $this->withinWindow($at)) {
            return false;
        }

        $now = $at
            ? \Illuminate\Support\Carbon::instance($at)->setTimezone($this->timezone)
            : now($this->timezone);

        if ($now->format('H:i') < $this->run_at) {
            return false;
        }

        return ! $this->last_run_at
            || $this->last_run_at->copy()->setTimezone($this->timezone)->toDateString() !== $now->toDateString();
    }

    /** Is right now inside the automation's permitted calling window? */
    public function withinWindow(?\DateTimeInterface $at = null): bool
    {
        $now = $at
            ? \Illuminate\Support\Carbon::instance($at)->setTimezone($this->timezone)
            : now($this->timezone);

        return $now->format('H:i') >= $this->window_start
            && $now->format('H:i') <= $this->window_end;
    }
}
