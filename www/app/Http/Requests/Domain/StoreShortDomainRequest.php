<?php

namespace App\Http\Requests\Domain;

use Illuminate\Foundation\Http\FormRequest;

class StoreShortDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:short_domains,name'],
            'target_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'redirect_type' => ['sometimes', 'string', 'in:301,302,307,308'],
            'forward_query' => ['sometimes', 'boolean'],
            'extra_query' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'extra_path' => ['sometimes', 'nullable', 'string', 'max:512'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
