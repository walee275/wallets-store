<?php

namespace App\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JazzCashGateway implements PaymentGateway
{
    public function driver(): string
    {
        return 'jazzcash';
    }

    public function initiate(Order $order, Payment $payment): PaymentInitiation
    {
        $config = $this->resolveConfig($payment);
        $txnRef = 'JC'.$payment->id.'-'.Str::upper(Str::random(8));
        $amount = number_format($payment->amount_cents / 100, 2, '.', '');

        $payment->update([
            'provider_ref' => $txnRef,
            'status' => PaymentStatus::Pending,
        ]);

        if (! $this->hasCredentials($config)) {
            return new PaymentInitiation(
                status: 'pending',
                redirectUrl: url("/webhooks/jazzcash/simulate/{$payment->id}"),
                providerRef: $txnRef,
                meta: [
                    'sandbox' => true,
                    'amount' => $amount,
                    'currency' => $payment->currency,
                ],
            );
        }

        $returnUrl = $config['return_url'] ?? config('services.jazzcash.return_url');
        $integritySalt = $config['integrity_salt'] ?? config('services.jazzcash.integrity_salt');
        $merchantId = $config['merchant_id'] ?? config('services.jazzcash.merchant_id');
        $password = $config['password'] ?? config('services.jazzcash.password');
        $endpoint = $config['endpoint'] ?? config('services.jazzcash.endpoint');

        $hash = $this->buildRequestHash(
            amount: $amount,
            billReference: $txnRef,
            description: 'Order '.$order->number,
            merchantId: $merchantId,
            password: $password,
            returnUrl: $returnUrl,
            txnRef: $txnRef,
            integritySalt: $integritySalt,
        );

        return new PaymentInitiation(
            status: 'redirect',
            redirectUrl: $endpoint,
            providerRef: $txnRef,
            meta: [
                'method' => 'POST',
                'fields' => [
                    'pp_Version' => '1.1',
                    'pp_TxnType' => 'MWALLET',
                    'pp_Language' => 'EN',
                    'pp_MerchantID' => $merchantId,
                    'pp_Password' => $password,
                    'pp_TxnRefNo' => $txnRef,
                    'pp_Amount' => $amount,
                    'pp_TxnCurrency' => $payment->currency,
                    'pp_TxnDateTime' => now()->format('YmdHis'),
                    'pp_BillReference' => $txnRef,
                    'pp_Description' => 'Order '.$order->number,
                    'pp_ReturnURL' => $returnUrl,
                    'pp_SecureHash' => $hash,
                    'ppmpf_1' => (string) $order->id,
                    'ppmpf_2' => (string) $payment->id,
                ],
            ],
        );
    }

    public function handleWebhook(Request $request): void
    {
        $paymentId = $request->input('ppmpf_2')
            ?? $request->route('payment')
            ?? $request->input('payment_id');

        if (! $paymentId) {
            abort(400, 'Payment reference missing.');
        }

        $payment = Payment::query()
            ->where('provider', $this->driver())
            ->where(function ($query) use ($paymentId) {
                $query->where('id', $paymentId)
                    ->orWhere('provider_ref', $request->input('pp_TxnRefNo'));
            })
            ->first();

        if (! $payment) {
            abort(404, 'Payment not found.');
        }

        $config = $this->resolveConfig($payment);
        $receivedHash = $request->input('pp_SecureHash') ?? $request->input('hash');

        if ($receivedHash && $this->hasCredentials($config)) {
            $expectedHash = $this->buildWebhookHash($request, $config);

            if (! hash_equals($expectedHash, $receivedHash)) {
                abort(403, 'Invalid JazzCash webhook signature.');
            }
        }

        $this->markPaymentPaid($payment, $request->all());
    }

    public function refund(Payment $payment, int $amountCents): void
    {
        throw new \RuntimeException('JazzCash refunds are not implemented in this sandbox gateway.');
    }

    protected function resolveConfig(Payment $payment): array
    {
        $methodConfig = $payment->paymentMethod?->config_json ?? [];

        return is_array($methodConfig) ? $methodConfig : [];
    }

    protected function hasCredentials(array $config): bool
    {
        $merchantId = $config['merchant_id'] ?? config('services.jazzcash.merchant_id');
        $password = $config['password'] ?? config('services.jazzcash.password');
        $integritySalt = $config['integrity_salt'] ?? config('services.jazzcash.integrity_salt');

        return filled($merchantId) && filled($password) && filled($integritySalt);
    }

    protected function buildRequestHash(
        string $amount,
        string $billReference,
        string $description,
        string $merchantId,
        string $password,
        string $returnUrl,
        string $txnRef,
        string $integritySalt,
    ): string {
        $payload = implode('&', [
            $integritySalt,
            $amount,
            $billReference,
            $description,
            'EN',
            $merchantId,
            $password,
            $returnUrl,
            $txnRef,
            'MWALLET',
            '1.1',
        ]);

        return hash_hmac('sha256', $payload, $integritySalt);
    }

    protected function buildWebhookHash(Request $request, array $config): string
    {
        $integritySalt = $config['integrity_salt'] ?? config('services.jazzcash.integrity_salt');
        $txnRef = (string) $request->input('pp_TxnRefNo', '');
        $amount = (string) $request->input('pp_Amount', '');
        $responseCode = (string) $request->input('pp_ResponseCode', '000');

        return hash_hmac('sha256', implode('|', [$txnRef, $amount, $responseCode]), $integritySalt);
    }

    protected function markPaymentPaid(Payment $payment, array $payload): void
    {
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($payment, $payload) {
            $payment->refresh();

            if ($payment->status === PaymentStatus::Paid) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'raw_payload_json' => $payload,
            ]);

            $payment->order()->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => OrderStatus::Processing,
            ]);
        });
    }
}
