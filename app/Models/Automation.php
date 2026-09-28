<?php

namespace App\Models;

use App\Support\CallStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'enabled', 'frequency', 'run_at', 'timezone',
        'expiry_within_days', 'customer_statuses', 'skip_do_not_call',
        'skip_already_renewed', 'skip_active_callback', 'min_days_between_calls',
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

        return $q->orderBy('policy_expiry_date')->orderBy('id');
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
