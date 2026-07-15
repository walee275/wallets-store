<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewOrderAdminMail extends OrderMailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New order · '.$this->order->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-order-admin',
            with: [
                'title' => 'New Order Received',
                'preheader' => 'A new order '.$this->order->number.' has been placed.',
                'customerFirstName' => $this->customerFirstName(),
                'ctaUrl' => route('admin.orders.show', $this->order),
                'ctaLabel' => 'View Order',
            ],
        );
    }
}
