<?php

namespace App\Support;

use Illuminate\Support\Str;

class SkuGenerator
{
    public static function generate(?string $prefix = 'SKU'): string
    {
        $prefix = strtoupper($prefix ?: 'SKU');

        return $prefix.'-'.Str::upper(Str::random(4)).'-'.now()->format('ymd').'-'.random_int(100, 999);
    }
}
