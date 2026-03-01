<?php

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class MoveFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by DriveFolderPolicy::move
    }

    public function rules(): array
    {
        return [
            // null means "move to root"
            'parent_id' => ['nullable', 'integer', 'exists:drive_folders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.exists' => 'The target folder does not exist.',
        ];
    }
}
