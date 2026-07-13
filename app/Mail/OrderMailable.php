<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

abstract class OrderMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
    ) {
        $this->order->loadMissing([
            'items.variant.images',
            'items.variant.product.images',
            'payments',
        ]);
    }

    abstract public function envelope(): Envelope;

    abstract public function content(): Content;

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    protected function customerFirstName(): string
    {
        $name = trim((string) data_get($this->order->shipping_address_json, 'name', ''));

        if ($name === '') {
            return 'there';
        }

        return explode(' ', $name)[0];
    }
}
