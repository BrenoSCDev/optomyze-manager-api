<?php

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class BulkDownloadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Per-file authorization handled in the controller
    }

    public function rules(): array
    {
        return [
            'file_ids'   => ['required', 'array', 'min:1', 'max:100'],
            'file_ids.*' => ['required', 'integer', 'exists:drive_files,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file_ids.required' => 'At least one file ID is required.',
            'file_ids.max'      => 'You may download at most 100 files at once.',
            'file_ids.*.exists' => 'One or more file IDs do not exist.',
        ];
    }
}
