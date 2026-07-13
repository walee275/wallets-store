<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Inventory\AdjustInventory;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddOrderNoteRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->trim()->toString();

        $orders = Order::query()
            ->with(['user:id,name,email'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'status' => $status,
            ],
            'statuses' => collect(OrderStatus::cases())->map(fn (OrderStatus $orderStatus) => [
                'value' => $orderStatus->value,
                'label' => $orderStatus->label(),
            ]),
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load([
            'user:id,name,email,phone',
            'items',
            'payments',
            'statusHistories.changedBy:id,name',
            'discount',
            'shippingRate.zone',
        ]);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
            'statuses' => collect(OrderStatus::cases())->map(fn (OrderStatus $orderStatus) => [
                'value' => $orderStatus->value,
                'label' => $orderStatus->label(),
            ]),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();
        $fromStatus = $order->status;
        $toStatus = OrderStatus::from($data['status']);

        $order->update(['status' => $toStatus]);

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'from_status' => $fromStatus->value,
            'to_status' => $toStatus->value,
            'note' => $data['note'] ?? null,
            'changed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Order status updated.');
    }

    public function addNote(AddOrderNoteRequest $request, Order $order): RedirectResponse
    {
        $note = trim($request->validated('note'));
        $existingNotes = trim((string) $order->notes);
        $order->update([
            'notes' => $existingNotes === ''
                ? $note
                : "{$existingNotes}\n\n{$note}",
        ]);

        return back()->with('success', 'Note added.');
    }

    public function refund(
        Request $request,
        Order $order,
        PaymentGatewayManager $gateways,
        AdjustInventory $adjustInventory,
    ): RedirectResponse {
        $request->validate([
            'amount_cents' => ['nullable', 'integer', 'min:1'],
            'restock' => ['sometimes', 'boolean'],
        ]);

        $payment = $order->payments()
            ->where('status', PaymentStatus::Paid)
            ->latest()
            ->first();

        if (! $payment) {
            return back()->with('error', 'No paid payment found for this order.');
        }

        $amount = $request->integer('amount_cents') ?: $payment->amount_cents;
        $amount = min($amount, $payment->amount_cents);

        DB::transaction(function () use ($order, $payment, $amount, $gateways, $adjustInventory, $request) {
            $gateways->gateway($payment->provider)->refund($payment, $amount);

            $isFull = $amount >= $payment->amount_cents;
            $payment->update([
                'status' => $isFull ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded,
            ]);

            $from = $order->status;
            $order->update([
                'payment_status' => $isFull ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded,
                'status' => $isFull ? OrderStatus::Refunded : $order->status,
            ]);

            if ($isFull) {
                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'from_status' => $from instanceof OrderStatus ? $from->value : $from,
                    'to_status' => OrderStatus::Refunded->value,
                    'note' => "Refunded {$amount} cents",
                    'changed_by' => $request->user()->id,
                ]);
            }

            if ($request->boolean('restock', true) && $isFull) {
                $order->loadMissing('items.variant');
                foreach ($order->items as $item) {
                    if ($item->variant_id) {
                        $adjustInventory->handle(
                            $item->variant,
                            $item->quantity,
                            'return',
                            $order->id,
                            $request->user()->id,
                            'Restock after refund',
                        );
                    }
                }
            }
        });

        return back()->with('success', 'Refund processed.');
    }
}
