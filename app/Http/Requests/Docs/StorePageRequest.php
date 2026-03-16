<?php

namespace App\Http\Requests\Docs;

use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'     => ['nullable', 'string', 'max:500'],
            'folder_id' => ['nullable', 'integer', 'exists:doc_folders,id'],
            'parent_id' => ['nullable', 'integer', 'exists:doc_pages,id'],
            'icon'      => ['nullable', 'string', 'max:10'],
            'cover_url' => ['nullable', 'string', 'max:2048'],
            'content'   => ['nullable', 'array'],
            'position'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
