<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_name' => 'sometimes|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'employees' => 'nullable|integer|min:0',
            'tax_id' => 'nullable|string|max:255',

            'contact_name' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',

            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'secondary_phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',

            'instagram' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'twitter_x' => 'nullable|string|max:255',
            'youtube' => 'nullable|string|max:255',
            'tiktok' => 'nullable|string|max:255',

            'country' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:50',

            'value' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:lead,prospect,active,inactive',
            'source' => 'nullable|string|max:255',
            'priority' => 'nullable|in:low,medium,high',

            'tags' => 'nullable|string',
            'notes' => 'nullable|string',

            'crm_active' => 'boolean',
        ];
    }
}
