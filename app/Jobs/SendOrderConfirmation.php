<?php

namespace App\Jobs;

use App\Mail\OrderConfirmedMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    public function handle(): void
    {
        $this->order->loadMissing([
            'items.variant.images',
            'items.variant.product.images',
        ]);

        Log::info('Sending order confirmation', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
            'email' => $this->order->email,
        ]);

        Mail::to($this->order->email)->send(new OrderConfirmedMail($this->order));
    }
}
