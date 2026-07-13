<?php

namespace App\Jobs;

use App\Models\Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendAbandonedCartReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Cart $cart,
    ) {}

    public function handle(): void
    {
        $this->cart->loadMissing(['items.variant.product', 'user']);

        Log::info('Abandoned cart reminder stub', [
            'cart_id' => $this->cart->id,
            'user_id' => $this->cart->user_id,
            'item_count' => $this->cart->items->count(),
        ]);
    }
}
