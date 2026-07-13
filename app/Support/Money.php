<?php

namespace App\Support;

class Money
{
    public static function format(int $cents, string $currency = 'PKR'): string
    {
        $amount = number_format($cents / 100, 2);

        return match (strtoupper($currency)) {
            'PKR' => "Rs {$amount}",
            'USD' => "$ {$amount}",
            default => "{$currency} {$amount}",
        };
    }

    public static function fromMajor(float|int|string $major): int
    {
        return (int) round(((float) $major) * 100);
    }

    public static function toMajor(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
