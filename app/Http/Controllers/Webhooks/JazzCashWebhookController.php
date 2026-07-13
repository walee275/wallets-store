<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Checkout\MarkPaymentPaid;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class JazzCashWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayManager $paymentGatewayManager): Response
    {
        $paymentGatewayManager->gateway('jazzcash')->handleWebhook($request);

        return response('OK', 200);
    }

    public function simulate(Payment $payment, MarkPaymentPaid $markPaymentPaid): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless($payment->provider === 'jazzcash', 404);

        $markPaymentPaid->handle($payment, 'Simulated JazzCash payment');

        return redirect()
            ->route('checkout.confirmation', $payment->order)
            ->with('success', 'Payment simulated successfully.');
    }
}
