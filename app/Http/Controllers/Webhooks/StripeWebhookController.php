<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayManager $paymentGatewayManager): Response
    {
        $paymentGatewayManager->gateway('stripe')->handleWebhook($request);

        return response('OK', 200);
    }
}
