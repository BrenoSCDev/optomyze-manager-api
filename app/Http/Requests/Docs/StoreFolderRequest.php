<?php

namespace App\Http\Requests\Docs;

use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:doc_folders,id'],
            'icon'      => ['nullable', 'string', 'max:10'],
            'position'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
