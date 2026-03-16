<?php

namespace App\Http\Requests\Docs;

use Illuminate\Foundation\Http\FormRequest;

class MovePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Target parent page — null moves it to the root of its folder
            'parent_id' => ['nullable', 'integer', 'exists:doc_pages,id'],
            // Target folder — null moves it to the root (no folder)
            'folder_id' => ['nullable', 'integer', 'exists:doc_folders,id'],
        ];
    }
}
