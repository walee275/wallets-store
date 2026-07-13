<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentFailedMail extends OrderMailable
{
    public function __construct(
        Order $order,
        public ?string $retryUrl = null,
    ) {
        parent::__construct($order);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action needed · payment failed for '.$this->order->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-failed',
            with: [
                'title' => 'Payment Failed',
                'preheader' => 'Action needed — we could not process payment for your order.',
                'customerFirstName' => $this->customerFirstName(),
                'ctaUrl' => $this->retryUrl ?? route('checkout.confirmation', $this->order),
                'ctaLabel' => 'Retry Payment',
            ],
        );
    }
}
