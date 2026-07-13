<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'homepage_banner_url' => ['nullable', 'url', 'max:2048'],
            'featured_collection_id' => ['nullable', 'integer', 'exists:collections,id'],
            'store_currency' => ['required', 'string', 'size:3'],
            'tax_mode' => ['required', 'string', Rule::in(['inclusive', 'exclusive', 'none'])],
        ];
    }
}
