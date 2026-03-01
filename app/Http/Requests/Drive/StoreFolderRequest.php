<?php

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by DriveFolderPolicy::create
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:drive_folders,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'The folder name is required.',
            'parent_id.exists'   => 'The selected parent folder does not exist.',
            'client_id.exists'   => 'The selected client does not exist.',
        ];
    }
}
