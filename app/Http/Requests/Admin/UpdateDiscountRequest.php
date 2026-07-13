<?php

namespace App\Http\Requests\Admin;

use App\Enums\DiscountType;
use App\Models\Discount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiscountRequest extends FormRequest
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
        /** @var Discount $discount */
        $discount = $this->route('discount');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('discounts', 'code')->ignore($discount->id)],
            'type' => ['required', Rule::enum(DiscountType::class)],
            'value' => ['required', 'integer', 'min:0'],
            'min_order_cents' => ['nullable', 'integer', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'max_uses_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ];
    }
}
