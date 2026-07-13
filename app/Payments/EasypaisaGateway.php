<?php

namespace App\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EasypaisaGateway implements PaymentGateway
{
    public function driver(): string
    {
        return 'easypaisa';
    }

    public function initiate(Order $order, Payment $payment): PaymentInitiation
    {
        $config = $this->resolveConfig($payment);
        $orderRef = 'EP'.$payment->id.'-'.Str::upper(Str::random(8));
        $amount = number_format($payment->amount_cents / 100, 2, '.', '');

        $payment->update([
            'provider_ref' => $orderRef,
            'status' => PaymentStatus::Pending,
        ]);

        if (! $this->hasCredentials($config)) {
            return new PaymentInitiation(
                status: 'pending',
                redirectUrl: url("/webhooks/easypaisa/simulate/{$payment->id}"),
                providerRef: $orderRef,
                meta: [
                    'sandbox' => true,
                    'amount' => $amount,
                    'currency' => $payment->currency,
                ],
            );
        }

        $storeId = $config['store_id'] ?? config('services.easypaisa.store_id');
        $hashKey = $config['hash_key'] ?? config('services.easypaisa.hash_key');
        $returnUrl = $config['return_url'] ?? config('services.easypaisa.return_url');
        $endpoint = $config['endpoint'] ?? config('services.easypaisa.endpoint');

        $hash = $this->buildRequestHash(
            amount: $amount,
            orderRef: $orderRef,
            storeId: $storeId,
            hashKey: $hashKey,
        );

        return new PaymentInitiation(
            status: 'redirect',
            redirectUrl: $endpoint,
            providerRef: $orderRef,
            meta: [
                'method' => 'POST',
                'fields' => [
                    'storeId' => $storeId,
                    'orderId' => $orderRef,
                    'transactionAmount' => $amount,
                    'transactionType' => 'MA',
                    'mobileAccountNo' => '',
                    'emailAddress' => $order->email,
                    'tokenExpiry' => now()->addHour()->format('Ymd His'),
                    'bankIdentificationNumber' => '',
                    'encryptedHashRequest' => $hash,
                    'merchantPaymentMethod' => 'MA',
                    'postBackURL' => $returnUrl,
                    'signature' => $hash,
                    'payment_id' => (string) $payment->id,
                    'order_id' => (string) $order->id,
                ],
            ],
        );
    }

    public function handleWebhook(Request $request): void
    {
        $paymentId = $request->input('payment_id')
            ?? $request->route('payment')
            ?? $request->input('orderRefNumber');

        if (! $paymentId) {
            abort(400, 'Payment reference missing.');
        }

        $payment = Payment::query()
            ->where('provider', $this->driver())
            ->where(function ($query) use ($paymentId, $request) {
                $query->where('id', $paymentId)
                    ->orWhere('provider_ref', $request->input('orderRefNumber', $paymentId));
            })
            ->first();

        if (! $payment) {
            abort(404, 'Payment not found.');
        }

        $config = $this->resolveConfig($payment);
        $receivedHash = $request->input('signature') ?? $request->input('hash');

        if ($receivedHash && $this->hasCredentials($config)) {
            $expectedHash = $this->buildWebhookHash($request, $config);

            if (! hash_equals($expectedHash, $receivedHash)) {
                abort(403, 'Invalid Easypaisa webhook signature.');
            }
        }

        $this->markPaymentPaid($payment, $request->all());
    }

    public function refund(Payment $payment, int $amountCents): void
    {
        throw new \RuntimeException('Easypaisa refunds are not implemented in this sandbox gateway.');
    }

    protected function resolveConfig(Payment $payment): array
    {
        $methodConfig = $payment->paymentMethod?->config_json ?? [];

        return is_array($methodConfig) ? $methodConfig : [];
    }

    protected function hasCredentials(array $config): bool
    {
        $storeId = $config['store_id'] ?? config('services.easypaisa.store_id');
        $hashKey = $config['hash_key'] ?? config('services.easypaisa.hash_key');

        return filled($storeId) && filled($hashKey);
    }

    protected function buildRequestHash(string $amount, string $orderRef, string $storeId, string $hashKey): string
    {
        return hash_hmac('sha256', implode('|', [$storeId, $orderRef, $amount]), $hashKey);
    }

    protected function buildWebhookHash(Request $request, array $config): string
    {
        $hashKey = $config['hash_key'] ?? config('services.easypaisa.hash_key');
        $orderRef = (string) $request->input('orderRefNumber', '');
        $amount = (string) $request->input('transactionAmount', '');
        $status = (string) $request->input('status', 'success');

        return hash_hmac('sha256', implode('|', [$orderRef, $amount, $status]), $hashKey);
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
