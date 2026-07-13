<?php

namespace App\Actions\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class MarkPaymentPaid
{
    public function handle(Payment $payment, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($payment, $note) {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === PaymentStatus::Paid) {
                return $payment;
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            $order = $payment->order()->lockForUpdate()->firstOrFail();
            $previousStatus = $order->status;

            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => OrderStatus::Processing,
            ]);

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'from_status' => $previousStatus->value,
                'to_status' => OrderStatus::Processing->value,
                'note' => $note ?? 'Payment marked as paid',
                'changed_by' => null,
            ]);

            return $payment->fresh();
        });
    }
}
