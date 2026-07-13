<?php

namespace App\Models;

use App\Support\MediaUrl;
use App\Support\ProductEmailImage;
use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'path',
        'email_thumb_path',
        'alt',
        'position',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Absolute CDN URL for the email thumbnail (generates on demand if missing).
     */
    public function emailAbsoluteUrl(): ?string
    {
        $disk = Storage::disk(config('media.disk', 'public'));
        $thumbPath = $this->email_thumb_path;

        if (! $thumbPath || ! $disk->exists($thumbPath)) {
            $thumbPath = ProductEmailImage::generateThumb($this);
        }

        return MediaUrl::absolute($thumbPath);
    }
}
