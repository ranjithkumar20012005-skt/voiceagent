<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'                   => ['required', 'string', 'max:120'],
            'enabled'                => ['nullable', 'boolean'],
            'frequency'              => ['required', Rule::in(['daily'])],
            'run_at'                 => ['required', 'date_format:H:i'],
            'timezone'               => ['required', 'string', Rule::in(timezone_identifiers_list())],

            'expiry_within_days'     => ['required', 'integer', 'min:0', 'max:365'],
            'customer_statuses'      => ['nullable', 'array'],
            'customer_statuses.*'    => [Rule::in(['pending', 'queued', 'in_progress', 'contacted', 'closed'])],
            'skip_do_not_call'       => ['nullable', 'boolean'],
            'skip_already_renewed'   => ['nullable', 'boolean'],
            'skip_active_callback'   => ['nullable', 'boolean'],
            'min_days_between_calls' => ['required', 'integer', 'min:0', 'max:90'],

            'max_calls_per_run'      => ['required', 'integer', 'min:1', 'max:10000'],
            'max_retries'            => ['required', 'integer', 'min:0', 'max:20'],
            'window_start'           => ['required', 'date_format:H:i'],
            'window_end'             => ['required', 'date_format:H:i', 'after:window_start'],
            'attempts_per_second'    => ['required', 'numeric', 'min:0.1', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Unchecked checkboxes are absent from the payload entirely.
        $this->merge([
            'enabled'              => $this->boolean('enabled'),
            'skip_do_not_call'     => $this->boolean('skip_do_not_call'),
            'skip_already_renewed' => $this->boolean('skip_already_renewed'),
            'skip_active_callback' => $this->boolean('skip_active_callback'),
        ]);
    }

    public function messages(): array
    {
        return [
            'window_end.after' => 'The calling window must end after it starts.',
        ];
    }
}
