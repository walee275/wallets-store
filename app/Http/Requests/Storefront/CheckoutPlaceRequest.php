<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutPlaceRequest extends FormRequest
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
            'email' => [
                Rule::requiredIf(! $this->user()),
                'nullable',
                'email',
                'max:255',
            ],
            'payment_driver' => [
                'required',
                'string',
                Rule::exists('payment_methods', 'driver')->where('is_enabled', true),
            ],
            'shipping_rate_id' => [
                'nullable',
                'integer',
                Rule::exists('shipping_rates', 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'save_address_for_future' => ['sometimes', 'boolean'],
            'billing_same_as_shipping' => ['sometimes', 'boolean'],
            'billing_address' => [
                Rule::requiredIf(! $this->boolean('billing_same_as_shipping', true)),
                'nullable',
                'array',
            ],
            'billing_address.name' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.phone' => ['nullable', 'string', 'max:50'],
            'billing_address.line1' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.line2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.state' => ['nullable', 'string', 'max:100'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:20'],
            'billing_address.country' => ['required_with:billing_address', 'string', 'size:2'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
