<?php

namespace App\Http\Requests\Link;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShortLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $linkId = $this->route('link')->id ?? $this->route('link');

        return [
            'code' => [
                'sometimes',
                'string',
                'max:32',
                'alpha_dash',
                Rule::unique('short_links')->where(function ($query) {
                    return $query->where('domain_id', $this->input('domain_id', $this->route('link')->domain_id ?? null));
                })->ignore($linkId),
            ],
            'domain_id' => ['sometimes', 'integer', Rule::exists('short_domains', 'id')],
            'target_url' => ['sometimes', 'url', 'max:2048'],
            'redirect_type' => ['sometimes', 'string', Rule::in(['301', '302', '307', '308'])],
            'forward_query' => ['sometimes', 'boolean'],
            'extra_query' => [
                'sometimes',
                'nullable',
                'string',
                'max:2048',
                function ($attr, $value, $fail) {
                    if ($value && $this->boolean('forward_query')) {
                        $fail('Невозможно использовать extra_query, когда включен forward_query');
                    }
                },
            ],
            'extra_path' => ['sometimes', 'nullable', 'string', 'max:512'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
