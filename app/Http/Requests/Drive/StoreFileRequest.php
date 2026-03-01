<?php

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
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

            // Allow common document, image, and archive formats
            // Max 50 MB — tune via config/environment as needed
            'file' => [
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
            'file.required' => 'A file is required.',
            'file.max'      => 'The file must not exceed 50 MB.',
            'file.mimes'    => 'The file type is not supported.',
            'folder_id.exists' => 'The target folder does not exist.',
            'client_id.exists' => 'The selected client does not exist.',
        ];
    }
}
