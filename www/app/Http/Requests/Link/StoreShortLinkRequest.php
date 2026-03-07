<?php

namespace App\Http\Requests\Link;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShortLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:32',
                'alpha_dash',
                Rule::unique('short_links')->where(function ($query) {
                    return $query->where('domain_id', $this->input('domain_id'));
                }),
            ],
            'domain_id' => ['required', 'integer', Rule::exists('short_domains', 'id')],
            'target_url' => ['required', 'url', 'max:2048'],
            'redirect_type' => ['required', 'string', Rule::in(['301', '302', '307', '308'])],
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

    public function messages(): array
    {
        return [
            'code.unique' => 'Этот код уже занят выбранным доменом',
        ];
    }
}
