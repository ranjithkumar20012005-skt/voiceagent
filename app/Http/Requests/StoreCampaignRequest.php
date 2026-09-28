<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:50'],
            'description'         => ['nullable', 'string', 'max:150'],
            'import_batch_id'     => ['nullable', 'integer', 'exists:import_batches,id'],
            'attempts_per_second' => ['nullable', 'numeric', 'min:0.1', 'max:500'],
            'starts_at'           => ['nullable', 'date'],
            'ends_at'             => ['nullable', 'date', 'after:starts_at'],

            // Which contacts to include when no import batch is chosen.
            'source'              => ['required', 'in:import_batch,filtered,selected'],
            'customer_ids'        => ['required_if:source,selected', 'array', 'max:10000'],
            'customer_ids.*'      => ['integer', 'exists:customers,id'],
            'expiry_within_days'  => ['nullable', 'integer', 'min:0', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'       => 'The campaign name must be 50 characters or fewer.',
            'ends_at.after'  => 'The campaign must end after it starts.',
        ];
    }
}
