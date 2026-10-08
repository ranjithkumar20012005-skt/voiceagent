<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Support\CallStatus;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallAttempt extends Model
{
    use BelongsToWorkspace;
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'customer_id', 'agent_id', 'attempt_id', 'campaign_id', 'cohort_id', 'interaction_id',
        'direction', 'status', 'completion_status', 'connectivity_status',
        'call_disposition', 'lead_generated', 'callback_required', 'callback_at',
        'failure_reason', 'duration_seconds', 'retry_attempt',
        'agent_phone_number', 'customer_phone_number', 'started_at', 'ended_at',
        'initial_agent_variables', 'final_agent_variables', 'output_agent_variables',
        'transcript', 'summary', 'language', 'raw_webhook_payload', 'user_id', 'webhook_received_at',
    ];

    protected function casts(): array
    {
        return [
            'lead_generated'          => 'boolean',
            'callback_required'       => 'boolean',
            'callback_at'             => 'datetime',
            'started_at'              => 'datetime',
            'ended_at'                => 'datetime',
            'webhook_received_at'     => 'datetime',
            'duration_seconds'        => 'integer',
            'retry_attempt'           => 'integer',
            'initial_agent_variables' => 'array',
            'final_agent_variables'   => 'array',
            'output_agent_variables'  => 'array',
            'transcript'              => 'array',
            'raw_webhook_payload'     => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /** At most one callback per call -- a unique index enforces it. */
    public function callback(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Callback::class, 'call_id');
    }

    public function usageRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UsageRecord::class, 'call_id');
    }

    // ---------------------------------------------------------------
    // Display helpers
    // ---------------------------------------------------------------

    public function getConnectivityLabelAttribute(): string
    {
        return CallStatus::connectivityLabels()[$this->connectivity_status]
            ?? ($this->status === CallStatus::QUEUED ? 'Pending' : 'Unavailable');
    }

    public function getOutcomeLabelAttribute(): string
    {
        return CallStatus::outcomeLabels()[$this->call_disposition] ?? 'Unknown';
    }

    public function getDisplayPhoneAttribute(): string
    {
        return PhoneNumber::display($this->customer_phone_number);
    }

    /** "4m 12s", or "--" when Sarvam has not reported a duration yet. */
    public function getDurationForHumansAttribute(): string
    {
        if ($this->duration_seconds === null) {
            return '--';
        }

        $m = intdiv($this->duration_seconds, 60);
        $s = $this->duration_seconds % 60;

        return $m > 0 ? "{$m}m {$s}s" : "{$s}s";
    }

    /**
     * Normalised transcript turns. Sarvam sends [{role, en_text}]; we tolerate
     * a couple of alternate key spellings but never invent content.
     *
     * @return array<int,array{role:string,text:string}>
     */
    public function transcriptTurns(): array
    {
        $turns = [];

        foreach ((array) ($this->transcript ?? []) as $turn) {
            if (! is_array($turn)) {
                continue;
            }

            $text = $turn['en_text'] ?? $turn['text'] ?? $turn['content'] ?? null;

            if ($text === null || trim((string) $text) === '') {
                continue;
            }

            $role = strtolower((string) ($turn['role'] ?? 'unknown'));

            $turns[] = [
                'role' => in_array($role, ['agent', 'assistant', 'bot'], true) ? 'agent' : 'customer',
                'text' => (string) $text,
            ];
        }

        return $turns;
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    public function scopeConnectivity(Builder $q, ?string $value): Builder
    {
        return $value ? $q->where('connectivity_status', $value) : $q;
    }

    public function scopeOutcome(Builder $q, ?string $value): Builder
    {
        return $value ? $q->where('call_disposition', $value) : $q;
    }

    public function scopeToday(Builder $q): Builder
    {
        return $q->whereDate('created_at', now()->toDateString());
    }
}
