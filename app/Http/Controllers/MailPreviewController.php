<?php

namespace App\Http\Controllers;

use App\Mail\NewOrderAdminMail;
use App\Mail\OrderConfirmedMail;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Mail\PaymentFailedMail;
use App\Models\Order;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MailPreviewController extends Controller
{
    public function __invoke(string $type): Response
    {
        if (! app()->environment('local')) {
            throw new NotFoundHttpException;
        }

        $order = Order::query()
            ->with(['items.variant.images', 'items.variant.product.images', 'payments'])
            ->latest('id')
            ->first();

        if (! $order) {
            return response(
                '<p style="font-family:sans-serif;padding:2rem;">No orders in the database. Place a test order or run <code>php artisan mail:preview</code>.</p>',
                404,
            );
        }

        $order->forceFill([
            'carrier' => $order->carrier ?: 'TCS',
            'tracking_number' => $order->tracking_number ?: 'TCS-994102',
            'tracking_url' => $order->tracking_url ?: 'https://www.tcsexpress.com/tracking',
        ]);

        $mailable = match ($type) {
            'order-confirmed', 'confirmed' => new OrderConfirmedMail($order),
            'order-shipped', 'shipped' => new OrderShippedMail($order),
            'order-delivered', 'delivered' => new OrderDeliveredMail($order, reviewUrl: url('/')),
            'payment-failed', 'failed' => new PaymentFailedMail($order),
            'new-order-admin', 'admin' => new NewOrderAdminMail($order),
            default => throw new NotFoundHttpException,
        };

        return response($mailable->render(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
