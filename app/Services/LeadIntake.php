<?php

namespace App\Services;

use App\Models\AgentLeadSource;
use App\Models\Customer;
use App\Support\PhoneNumber;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Turns whatever a lead source posted into a customer we can call.
 *
 * Every source names its fields differently -- a website form sends `phone`, a
 * lead-ads payload nests `field_data` as an array of name/values, an automation
 * tool sends whatever the user mapped. Rather than guess, this reads the
 * source's own `field_map` first and only then falls back to a list of common
 * names. A lead with no usable phone number is rejected and counted, never
 * stored as a customer nobody can ring.
 */
class LeadIntake
{
    /** Field names commonly carrying each value, tried in order. */
    private const CANDIDATES = [
        'phone' => ['phone', 'phone_number', 'phoneNumber', 'mobile', 'mobile_number', 'contact', 'contact_number', 'tel', 'telephone', 'number', 'whatsapp'],
        'name'  => ['name', 'full_name', 'fullName', 'first_name', 'firstName', 'customer_name', 'lead_name'],
        'email' => ['email', 'email_address', 'emailAddress', 'e_mail'],
        'city'  => ['city', 'location', 'town', 'area'],
        'company' => ['company', 'company_name', 'organisation', 'organization', 'business'],
        'note'  => ['note', 'notes', 'message', 'comments', 'enquiry', 'requirement'],
    ];

    /**
     * @param  array<string,mixed>  $payload
     * @return array{customer:?Customer,reason:?string}
     */
    public function capture(AgentLeadSource $source, array $payload): array
    {
        $flat = $this->flatten($payload);

        $rawPhone = $this->value($source, $flat, 'phone');
        $phone    = PhoneNumber::normalize(is_scalar($rawPhone) ? (string) $rawPhone : null);

        if (! $phone) {
            $source->increment('rejected_count');

            Log::info('lead.rejected', [
                'workspace_id' => $source->workspace_id,
                'agent_id'     => $source->agent_id,
                'action'       => 'capture_lead',
                'reason'       => 'no_usable_phone_number',
            ]);

            return ['customer' => null, 'reason' => 'No usable phone number in the lead.'];
        }

        $name    = $this->string($this->value($source, $flat, 'name'));
        $email   = $this->string($this->value($source, $flat, 'email'));
        $city    = $this->string($this->value($source, $flat, 'city'));
        $company = $this->string($this->value($source, $flat, 'company'));
        $note    = $this->string($this->value($source, $flat, 'note'));

        // Matched on the number so a repeat enquiry updates the person we
        // already hold rather than creating a second record of them.
        $customer = Customer::firstOrNew(['phone_number' => $phone]);

        $customer->fill(array_filter([
            'name'     => $name ?: $customer->name,
            'email'    => $email ?: $customer->email,
            'location' => $city ?: $customer->location,
            'company'  => $company ?: $customer->company,
            'source'   => $source->kind,
            'notes'    => $note ?: $customer->notes,
        ], fn ($v) => $v !== null && $v !== ''));

        // Keep the original payload against the customer, so a field we did not
        // map is still recoverable rather than discarded.
        $customer->custom_fields = array_merge(
            (array) ($customer->custom_fields ?? []),
            ['last_lead' => Arr::only($flat, array_slice(array_keys($flat), 0, 25))],
        );

        if (! $customer->exists) {
            $customer->workspace_id = $source->workspace_id;
            $customer->customer_status = 'pending';
        }

        $customer->save();

        $source->forceFill([
            'lead_count'   => $source->lead_count + 1,
            'last_lead_at' => now(),
        ])->save();

        return ['customer' => $customer, 'reason' => null];
    }

    /**
     * Flattens a nested payload into single-level keys, and expands the
     * name/values shape lead-ads providers use so `field_data` entries become
     * ordinary fields.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function flatten(array $payload): array
    {
        $flat = [];

        $containers = ['field_data', 'fields', 'answers'];

        // Lead-ads style: [{name: "phone_number", values: ["+9190..."]}, ...]
        foreach ($containers as $key) {
            $entries = $payload[$key] ?? null;

            if (! is_array($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $name  = $entry['name'] ?? $entry['key'] ?? $entry['label'] ?? null;
                $value = $entry['values'][0] ?? $entry['value'] ?? $entry['answer'] ?? null;

                if (is_string($name) && $value !== null) {
                    $flat[$this->normaliseKey($name)] = $value;
                }
            }
        }

        // The containers above are already expanded. Walking them again here
        // would add entries like `name => "full_name"` -- the field's own label
        // read as if it were the value -- and that label would then win over the
        // real one, so they are excluded.
        $rest = Arr::except($payload, $containers);

        foreach (Arr::dot($rest) as $key => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            // Keep the leaf name as well as the dotted path, so both
            // `lead.phone` and `phone` resolve.
            $flat[$this->normaliseKey($key)] ??= $value;
            $leaf = $this->normaliseKey((string) Arr::last(explode('.', $key)));
            $flat[$leaf] ??= $value;
        }

        return $flat;
    }

    /** The mapped field if the source named one, otherwise a common name. */
    private function value(AgentLeadSource $source, array $flat, string $logical): mixed
    {
        $mapped = $source->field_map[$logical] ?? null;

        if (is_string($mapped) && $mapped !== '') {
            $key = $this->normaliseKey($mapped);

            if (array_key_exists($key, $flat)) {
                return $flat[$key];
            }
        }

        foreach (self::CANDIDATES[$logical] ?? [] as $candidate) {
            $key = $this->normaliseKey($candidate);

            if (array_key_exists($key, $flat) && $flat[$key] !== null && $flat[$key] !== '') {
                return $flat[$key];
            }
        }

        return null;
    }

    private function normaliseKey(string $key): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($key)) ?? $key);
    }

    private function string(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : mb_substr($text, 0, 190);
    }
}
