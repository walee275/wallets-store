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

    /**
     * Email-safe money format, e.g. "PKR 15,150".
     */
    public static function formatEmail(int $cents, string $currency = 'PKR'): string
    {
        $major = $cents / 100;
        $amount = fmod($major, 1.0) === 0.0
            ? number_format($major, 0)
            : number_format($major, 2);

        return strtoupper($currency).' '.$amount;
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
