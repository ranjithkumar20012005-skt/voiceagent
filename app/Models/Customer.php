<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_identifier', 'name', 'phone_number', 'policy_number',
        'registered_mobile', 'policy_expiry_date', 'renewal_premium',
        'preferred_language', 'customer_status', 'last_outcome', 'last_connectivity',
        'last_call_at', 'next_callback_at', 'call_count', 'do_not_call', 'notes',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'policy_expiry_date' => 'date',
            'renewal_premium'    => 'decimal:2',
            'last_call_at'       => 'datetime',
            'next_callback_at'   => 'datetime',
            'do_not_call'        => 'boolean',
            'call_count'         => 'integer',
        ];
    }

    public function callAttempts(): HasMany
    {
        return $this->hasMany(CallAttempt::class)->latest('id');
    }

    public function latestCallAttempt()
    {
        return $this->hasOne(CallAttempt::class)->latestOfMany();
    }

    public function importBatch()
    {
        return $this->belongsTo(ImportBatch::class);
    }

    // ---------------------------------------------------------------
    // Mutators -- phone numbers are always stored E.164.
    // ---------------------------------------------------------------

    public function setPhoneNumberAttribute($value): void
    {
        $this->attributes['phone_number'] = PhoneNumber::normalize($value) ?? $value;
    }

    public function setRegisteredMobileAttribute($value): void
    {
        $this->attributes['registered_mobile'] = $value
            ? (PhoneNumber::normalize($value) ?? $value)
            : null;
    }

    public function getDisplayPhoneAttribute(): string
    {
        return PhoneNumber::display($this->phone_number);
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    public function scopeCallable(Builder $q): Builder
    {
        return $q->where('do_not_call', false)->whereNotNull('phone_number');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

        return $q->where(function (Builder $w) use ($like) {
            $w->where('name', 'like', $like)
              ->orWhere('phone_number', 'like', $like)
              ->orWhere('policy_number', 'like', $like)
              ->orWhere('customer_identifier', 'like', $like);
        });
    }

    /**
     * Variables handed to the Sarvam agent for this customer, built from the
     * central mapping in config/sarvam.php. Values are cast to strings because
     * agent variables are text placeholders.
     *
     * @return array<string,string>
     */
    public function agentVariables(): array
    {
        $out = [];

        foreach ((array) config('sarvam.agent_variables', []) as $sarvamKey => $localField) {
            $value = $this->{$localField};

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }

            if ($value === null || $value === '') {
                continue;
            }

            $out[$sarvamKey] = (string) $value;
        }

        return $out;
    }
}
