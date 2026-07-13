<?php

namespace App\Http\Requests\Admin;

use App\Models\Collection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionRequest extends FormRequest
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
        /** @var Collection $collection */
        $collection = $this->route('collection');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('collections', 'slug')->ignore($collection->id)],
            'type' => ['required', 'string', Rule::in(['manual', 'smart'])],
            'rules_json' => ['nullable', 'array'],
            'is_featured' => ['boolean'],
        ];
    }
}
