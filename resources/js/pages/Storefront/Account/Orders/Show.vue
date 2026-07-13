<script setup lang="ts">
import { useFormatMoney } from '@/composables/useFormatMoney';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface OrderItem {
    id: number;
    product_title: string;
    quantity: number;
    unit_price_cents: number;
    line_total_cents: number;
}

interface StatusHistory {
    id: number;
    from_status?: string | null;
    to_status: string;
    note?: string | null;
    created_at: string;
    changedBy?: { name: string } | null;
}

interface Order {
    id: number;
    number: string;
    status: string;
    email: string;
    subtotal_cents: number;
    discount_cents: number;
    shipping_cents: number;
    tax_cents: number;
    total_cents: number;
    placed_at?: string | null;
    shipping_address_json?: Record<string, string> | null;
    items: OrderItem[];
    shippingRate?: { name: string } | null;
    payments?: { status: string; amount_cents: number; paymentMethod?: { name: string } }[];
    statusHistories?: StatusHistory[];
}

defineProps<{
    order: Order;
}>();

const { formatMoney } = useFormatMoney();
</script>

<template>
    <Head :title="`Order ${order.number}`" />

    <StorefrontLayout>
        <Link :href="route('account.orders')" class="text-sm text-teal-800 hover:underline">&larr; Back to orders</Link>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Order #{{ order.number }}</h1>
                <p class="text-sm text-stone-500">
                    Placed {{ order.placed_at ? new Date(order.placed_at).toLocaleString() : '—' }}
                    · Status: <span class="capitalize">{{ order.status }}</span>
                </p>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_320px]">
            <div class="space-y-6">
                <section class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-semibold">Items</h2>
                    <ul class="mt-4 divide-y divide-stone-100">
                        <li v-for="item in order.items" :key="item.id" class="flex justify-between gap-4 py-3 text-sm">
                            <span>{{ item.product_title }} × {{ item.quantity }}</span>
                            <span>{{ formatMoney(item.line_total_cents) }}</span>
                        </li>
                    </ul>
                </section>

                <section v-if="order.statusHistories?.length" class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-semibold">Status history</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li v-for="entry in order.statusHistories" :key="entry.id">
                            <span class="font-medium capitalize">{{ entry.to_status }}</span>
                            <span class="text-stone-500"> — {{ new Date(entry.created_at).toLocaleString() }}</span>
                            <p v-if="entry.note" class="text-stone-600">{{ entry.note }}</p>
                        </li>
                    </ul>
                </section>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-semibold">Summary</h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ formatMoney(order.subtotal_cents) }}</dd></div>
                        <div v-if="order.discount_cents" class="flex justify-between text-teal-800"><dt>Discount</dt><dd>-{{ formatMoney(order.discount_cents) }}</dd></div>
                        <div class="flex justify-between"><dt>Shipping</dt><dd>{{ formatMoney(order.shipping_cents) }}</dd></div>
                        <div class="flex justify-between"><dt>Tax</dt><dd>{{ formatMoney(order.tax_cents) }}</dd></div>
                        <div class="flex justify-between border-t border-stone-200 pt-2 font-semibold"><dt>Total</dt><dd>{{ formatMoney(order.total_cents) }}</dd></div>
                    </dl>
                </div>

                <div v-if="order.shipping_address_json" class="rounded-lg border border-stone-200 bg-white p-5 text-sm">
                    <h2 class="font-semibold">Shipping address</h2>
                    <p class="mt-2 text-stone-600">
                        {{ order.shipping_address_json.name }}<br />
                        {{ order.shipping_address_json.line1 }}<br />
                        <template v-if="order.shipping_address_json.line2">{{ order.shipping_address_json.line2 }}<br /></template>
                        {{ order.shipping_address_json.city }}, {{ order.shipping_address_json.country }}
                    </p>
                </div>

                <div v-if="order.payments?.length" class="rounded-lg border border-stone-200 bg-white p-5 text-sm">
                    <h2 class="font-semibold">Payment</h2>
                    <p v-for="payment in order.payments" :key="payment.status" class="mt-2 text-stone-600">
                        {{ payment.paymentMethod?.name }} — {{ formatMoney(payment.amount_cents) }} ({{ payment.status }})
                    </p>
                </div>
            </aside>
        </div>
    </StorefrontLayout>
</template>
