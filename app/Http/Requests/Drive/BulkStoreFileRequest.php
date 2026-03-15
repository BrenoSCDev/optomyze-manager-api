<?php

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by DriveFilePolicy::create
    }

    public function rules(): array
    {
        return [
            'folder_id' => ['nullable', 'integer', 'exists:drive_folders,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],

            'files'   => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => [
                'required',
                'file',
                'max:51200', // 50 MB in kilobytes
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,'
                    . 'png,jpg,jpeg,gif,webp,svg,'
                    . 'zip,rar,7z,'
                    . 'mp4,mov,avi,mkv',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required'    => 'At least one file is required.',
            'files.max'         => 'You may upload at most 20 files at once.',
            'files.*.required'  => 'Each item must be a file.',
            'files.*.max'       => 'Each file must not exceed 50 MB.',
            'files.*.mimes'     => 'One or more file types are not supported.',
            'folder_id.exists'  => 'The target folder does not exist.',
            'client_id.exists'  => 'The selected client does not exist.',
        ];
    }
}
