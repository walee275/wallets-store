<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class MediaUrl
{
    /**
     * Build an absolute URL suitable for email clients (never relative).
     */
    public static function absolute(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $cdn = rtrim((string) config('media.cdn_url'), '/');

        if ($cdn !== '') {
            return $cdn.'/'.ltrim($path, '/');
        }

        return Storage::disk(config('media.disk', 'public'))->url(ltrim($path, '/'));
    }
}
