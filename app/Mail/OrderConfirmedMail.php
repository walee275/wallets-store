<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderConfirmedMail extends OrderMailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order confirmed · '.$this->order->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmed',
            with: [
                'title' => 'Order Confirmation',
                'preheader' => 'Your order has been confirmed and is being prepared by hand.',
                'customerFirstName' => $this->customerFirstName(),
                'ctaUrl' => route('checkout.confirmation', $this->order),
                'ctaLabel' => 'Track Your Order',
            ],
        );
    }
}
