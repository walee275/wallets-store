<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product->id)],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'brand' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'max:10240'],
            'removed_image_ids' => ['nullable', 'array'],
            'removed_image_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('product_images', 'id')->where('product_id', $product->id),
            ],
            'primary_image_id' => [
                'nullable',
                'integer',
                Rule::exists('product_images', 'id')->where('product_id', $product->id),
            ],
            'primary_image_upload_index' => ['nullable', 'integer', 'min:0'],
            'default_variant' => ['required', 'array'],
            'default_variant.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'default_variant.sku' => ['nullable', 'string', 'max:255'],
            'default_variant.price' => ['required', 'numeric', 'min:0'],
            'default_variant.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'default_variant.cost_price' => ['nullable', 'numeric', 'min:0'],
            'default_variant.stock' => ['required', 'integer', 'min:0'],
            'default_variant.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'default_variant.weight_grams' => ['nullable', 'integer', 'min:0'],
            'default_variant.currency' => ['nullable', 'string', 'size:3'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variants.*.weight_grams' => ['nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
