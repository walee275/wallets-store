<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Checkout\MarkPaymentPaid;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EasypaisaWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayManager $paymentGatewayManager): Response
    {
        $paymentGatewayManager->gateway('easypaisa')->handleWebhook($request);

        return response('OK', 200);
    }

    public function simulate(Payment $payment, MarkPaymentPaid $markPaymentPaid): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless($payment->provider === 'easypaisa', 404);

        $markPaymentPaid->handle($payment, 'Simulated Easypaisa payment');

        return redirect()
            ->route('checkout.confirmation', $payment->order)
            ->with('success', 'Payment simulated successfully.');
    }
}
