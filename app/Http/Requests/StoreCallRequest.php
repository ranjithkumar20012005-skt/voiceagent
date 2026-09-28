<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'customer_id'       => ['nullable', 'integer', 'exists:customers,id'],
            'name'              => ['nullable', 'string', 'max:120'],
            'phone_number'      => ['required', 'string', 'max:24'],
            'policy_number'     => ['nullable', 'string', 'max:64'],
            'registered_mobile' => ['nullable', 'string', 'max:24'],
            'language'          => ['nullable', 'string', Rule::in(config('sarvam.languages', []))],
            'agent_id'          => ['nullable', 'integer', Rule::exists('agents', 'id')->where('status', 'active')],

            // Free-form extras forwarded to the agent as variables.
            'variables'         => ['nullable', 'array', 'max:20'],
            'variables.*.key'   => ['required_with:variables', 'string', 'max:64', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/'],
            'variables.*.value' => ['nullable', 'string', 'max:500'],

            // Guards against a double-submitted modal.
            'idempotency_key'   => ['nullable', 'string', 'max:64'],
        ];
    }

    /** Normalise the number before validation so the rule below sees E.164. */
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone_number')) {
            $this->merge([
                'phone_number' => PhoneNumber::normalize($this->input('phone_number')) ?? $this->input('phone_number'),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->filled('phone_number') && ! PhoneNumber::isValid($this->input('phone_number'))) {
                $v->errors()->add('phone_number', 'Enter a valid phone number, for example +91 98765 43210.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'variables.*.key.regex' => 'Variable names must start with a letter and contain only letters, numbers and underscores.',
        ];
    }

    /**
     * Extra agent variables, as a flat map.
     *
     * @return array<string,string>
     */
    public function extraVariables(): array
    {
        $out = [];

        foreach ((array) $this->input('variables', []) as $row) {
            $key = trim((string) ($row['key'] ?? ''));

            if ($key === '') {
                continue;
            }

            $out[$key] = (string) ($row['value'] ?? '');
        }

        return $out;
    }
}
