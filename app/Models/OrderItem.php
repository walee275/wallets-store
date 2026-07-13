<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'variant_id',
        'product_title',
        'variant_sku',
        'options_json',
        'quantity',
        'unit_price_cents',
        'total_cents',
    ];

    protected function casts(): array
    {
        return [
            'options_json' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    protected function variantColor(): Attribute
    {
        return Attribute::get(fn () => $this->optionValue(['Color', 'Colour']));
    }

    protected function variantMaterial(): Attribute
    {
        return Attribute::get(fn () => $this->optionValue(['Material', 'Leather']));
    }

    /**
     * Absolute CDN URL for this line item's product image, or null when missing.
     */
    public function emailThumbnailUrl(): ?string
    {
        if (! $this->variant) {
            return null;
        }

        return Product::emailThumbnailUrl($this->variant);
    }

    public function emailAltText(): string
    {
        $details = trim(implode(' ', array_filter([
            $this->variant_color,
            $this->variant_material,
        ])));

        if ($details === '') {
            $fallback = collect($this->options_json ?? [])
                ->pluck('value')
                ->filter()
                ->implode(' ');

            return $fallback !== ''
                ? "{$this->product_title} in {$fallback}"
                : $this->product_title;
        }

        return "{$this->product_title} in {$details}";
    }

    public function emailVariantMeta(): string
    {
        $parts = collect($this->options_json ?? [])
            ->pluck('value')
            ->filter()
            ->values();

        if ($parts->isEmpty() && $this->variant_sku) {
            $parts = collect([$this->variant_sku]);
        }

        $parts->push('Qty '.$this->quantity);

        return $parts->implode(' · ');
    }

    /**
     * @param  list<string>  $names
     */
    protected function optionValue(array $names): ?string
    {
        foreach ($this->options_json ?? [] as $option) {
            $type = (string) ($option['type'] ?? '');

            foreach ($names as $name) {
                if (strcasecmp($type, $name) === 0) {
                    $value = trim((string) ($option['value'] ?? ''));

                    return $value !== '' ? $value : null;
                }
            }
        }

        return null;
    }
}
