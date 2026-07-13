<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderDeliveredMail extends OrderMailable
{
    public function __construct(
        Order $order,
        public ?string $reviewUrl = null,
    ) {
        parent::__construct($order);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Delivered · '.$this->order->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-delivered',
            with: [
                'title' => 'Order Delivered',
                'preheader' => 'Your order has been delivered. We hope you love it.',
                'customerFirstName' => $this->customerFirstName(),
                'ctaUrl' => $this->reviewUrl,
                'ctaLabel' => $this->reviewUrl ? 'Leave a Review' : null,
            ],
        );
    }
}
