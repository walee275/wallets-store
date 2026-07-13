<?php

namespace App\Support;

use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ProductEmailImage
{
    /**
     * Absolute CDN URL for a variant's primary image at email-thumb size.
     */
    public static function urlForVariant(ProductVariant $variant): ?string
    {
        $variant->loadMissing(['images', 'product.images']);

        $image = self::resolvePrimaryImage($variant);

        if (! $image) {
            return null;
        }

        return $image->emailAbsoluteUrl();
    }

    public static function resolvePrimaryImage(ProductVariant $variant): ?ProductImage
    {
        $variantImages = $variant->images;

        return $variantImages->firstWhere('is_primary', true)
            ?? $variantImages->sortBy('position')->first()
            ?? $variant->product?->images->firstWhere('is_primary', true)
            ?? $variant->product?->images->sortBy('position')->first();
    }

    /**
     * Create (or regenerate) the square email thumb next to the source image.
     */
    public static function generateThumb(ProductImage $image): ?string
    {
        $disk = Storage::disk(config('media.disk', 'public'));

        if (! $image->path || ! $disk->exists($image->path)) {
            return null;
        }

        $width = (int) config('media.email_thumb.width', 640);
        $height = (int) config('media.email_thumb.height', 640);
        $directory = trim((string) config('media.email_thumb.directory', 'products/email'), '/');

        $extension = pathinfo($image->path, PATHINFO_EXTENSION) ?: 'jpg';
        $filename = pathinfo($image->path, PATHINFO_FILENAME) ?: (string) Str::uuid();
        $thumbPath = $directory.'/'.$filename.'_'.$width.'x'.$height.'.'.$extension;

        $encoded = Image::decodeBinary($disk->get($image->path))
            ->cover($width, $height)
            ->encodeUsingFileExtension($extension);

        $disk->put($thumbPath, (string) $encoded);

        $image->forceFill(['email_thumb_path' => $thumbPath])->save();

        return $thumbPath;
    }
}
