<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UploadCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:' . (int) config('sarvam.upload.max_file_kb'),
                // Extension *and* MIME are both checked: `mimes` inspects the
                // real content type, not just the filename the client sent.
                'mimes:csv,txt,xlsx,xls,zip',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel,'
                    . 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
                    . 'application/zip,application/x-zip-compressed,application/octet-stream',
            ],
        ];
    }

    /**
     * `application/octet-stream` has to be allowed above because Windows
     * browsers send it for .xlsx, so re-check the extension explicitly here.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $file = $this->file('file');

                if (! $file) {
                    return;
                }

                $ext = strtolower($file->getClientOriginalExtension());

                if (! in_array($ext, ['csv', 'xlsx', 'xls', 'zip'], true)) {
                    $validator->errors()->add('file', 'Upload a .csv, .xlsx, .xls or .zip file.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'file.max'       => 'The file is larger than the ' . round(config('sarvam.upload.max_file_kb') / 1024) . ' MB limit.',
            'file.mimes'     => 'Upload a .csv, .xlsx, .xls or .zip file.',
            'file.mimetypes' => 'That file type is not supported. Upload a .csv, .xlsx, .xls or .zip file.',
        ];
    }
}
