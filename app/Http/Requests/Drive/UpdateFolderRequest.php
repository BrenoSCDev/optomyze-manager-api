<?php

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by DriveFolderPolicy::update
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            // Pass null explicitly to disassociate from a client
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'The folder name is required.',
            'client_id.exists' => 'The selected client does not exist.',
        ];
    }
}
