<?php

namespace App\Http\Requests\Docs;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'icon'     => ['nullable', 'string', 'max:10'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
