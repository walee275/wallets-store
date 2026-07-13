<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderShippedMail extends OrderMailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order shipped · '.$this->order->number,
        );
    }

    public function content(): Content
    {
        $trackingUrl = $this->order->tracking_url
            ?: route('checkout.confirmation', $this->order);

        return new Content(
            view: 'emails.order-shipped',
            with: [
                'title' => 'Order Shipped',
                'preheader' => 'Your order is on its way'.($this->order->tracking_number ? ' · '.$this->order->tracking_number : '').'.',
                'customerFirstName' => $this->customerFirstName(),
                'ctaUrl' => $trackingUrl,
                'ctaLabel' => 'Track Shipment',
            ],
        );
    }
}
