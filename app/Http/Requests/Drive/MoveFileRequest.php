<?php

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class MoveFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by DriveFilePolicy::move
    }

    public function rules(): array
    {
        return [
            // null means "move to root (no folder)"
            'folder_id' => ['nullable', 'integer', 'exists:drive_folders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'folder_id.exists' => 'The target folder does not exist.',
        ];
    }
}
