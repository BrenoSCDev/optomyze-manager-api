<?php

namespace App\Http\Requests\Docs;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'     => ['sometimes', 'string', 'max:500'],
            'icon'      => ['sometimes', 'nullable', 'string', 'max:10'],
            'cover_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'content'   => ['sometimes', 'nullable', 'array'],
            'position'  => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
