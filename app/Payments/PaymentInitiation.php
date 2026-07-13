<?php

namespace App\Payments;

class PaymentInitiation
{
    public function __construct(
        public string $status,
        public ?string $redirectUrl = null,
        public ?string $clientSecret = null,
        public ?string $providerRef = null,
        public array $meta = [],
    ) {}
}
