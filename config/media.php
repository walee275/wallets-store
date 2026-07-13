<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media disk
    |--------------------------------------------------------------------------
    |
    | Product photography is stored on this disk. Use "public" locally and
    | "s3" (or a CloudFront-backed S3 disk) in production.
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | CDN / absolute URL base
    |--------------------------------------------------------------------------
    |
    | Transactional emails require absolute image URLs. Prefer a CloudFront
    | (or equivalent) base URL. Falls back to the disk's configured URL
    | (APP_URL/storage for the public disk, AWS_URL for S3).
    |
    */

    'cdn_url' => env('MEDIA_CDN_URL', env('AWS_URL')),

    /*
    |--------------------------------------------------------------------------
    | Email thumbnail conversion
    |--------------------------------------------------------------------------
    |
    | Square cover crop used in order emails. Rendered at 72×72 via HTML
    | width/height attributes; 640px source supports 2× retina displays.
    |
    */

    'email_thumb' => [
        'width' => 640,
        'height' => 640,
        'directory' => 'products/email',
    ],

];
