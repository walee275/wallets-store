<?php

namespace App\Payments;

use App\Models\PaymentMethod;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    protected array $drivers = [
        'cod' => CodGateway::class,
        'stripe' => StripeGateway::class,
        'jazzcash' => JazzCashGateway::class,
        'easypaisa' => EasypaisaGateway::class,
    ];

    public function gateway(string $driver): PaymentGateway
    {
        $driver = strtolower($driver);

        if (! isset($this->drivers[$driver])) {
            throw new InvalidArgumentException("Payment gateway [{$driver}] is not supported.");
        }

        return app($this->drivers[$driver]);
    }

    public function enabledGateways(): Collection
    {
        return PaymentMethod::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->get();
    }
}
