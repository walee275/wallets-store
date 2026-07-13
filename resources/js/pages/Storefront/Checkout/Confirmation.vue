<script setup lang="ts">
import { Button } from '@/components/ui/button';
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
    items: OrderItem[];
    shippingRate?: { name: string } | null;
    payments?: { status: string; paymentMethod?: { name: string } }[];
}

defineProps<{
    order: Order;
}>();

const { formatMoney } = useFormatMoney();
</script>

<template>
    <Head :title="`Order ${order.number}`" />

    <StorefrontLayout>
        <div class="mx-auto max-w-lg text-center">
            <div class="mb-2 text-4xl">✓</div>
            <h1 class="text-2xl font-semibold">Thank you for your order!</h1>
            <p class="mt-2 text-stone-600">Order <span class="font-medium text-[#1a1a1a]">#{{ order.number }}</span> has been placed.</p>
            <p v-if="order.placed_at" class="mt-1 text-sm text-stone-500">{{ new Date(order.placed_at).toLocaleString() }}</p>
        </div>

        <div class="mx-auto mt-8 max-w-lg rounded-lg border border-stone-200 bg-white p-5">
            <h2 class="font-semibold">Order summary</h2>
            <ul class="mt-4 space-y-2 text-sm">
                <li v-for="item in order.items" :key="item.id" class="flex justify-between gap-2">
                    <span>{{ item.product_title }} × {{ item.quantity }}</span>
                    <span>{{ formatMoney(item.line_total_cents) }}</span>
                </li>
            </ul>
            <dl class="mt-4 space-y-2 border-t border-stone-200 pt-4 text-sm">
                <div class="flex justify-between">
                    <dt>Subtotal</dt>
                    <dd>{{ formatMoney(order.subtotal_cents) }}</dd>
                </div>
                <div v-if="order.discount_cents" class="flex justify-between text-teal-800">
                    <dt>Discount</dt>
                    <dd>-{{ formatMoney(order.discount_cents) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>Shipping<span v-if="order.shippingRate"> ({{ order.shippingRate.name }})</span></dt>
                    <dd>{{ formatMoney(order.shipping_cents) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>Tax</dt>
                    <dd>{{ formatMoney(order.tax_cents) }}</dd>
                </div>
                <div class="flex justify-between border-t border-stone-200 pt-2 font-semibold">
                    <dt>Total</dt>
                    <dd>{{ formatMoney(order.total_cents) }}</dd>
                </div>
            </dl>
            <p v-if="order.payments?.length" class="mt-4 text-sm text-stone-600">
                Payment: {{ order.payments[0].paymentMethod?.name ?? 'Pending' }} — {{ order.payments[0].status }}
            </p>
        </div>

        <div class="mx-auto mt-6 flex max-w-lg flex-col gap-3 sm:flex-row sm:justify-center">
            <Button as-child variant="outline">
                <Link :href="route('account.orders.show', order.id)">View order details</Link>
            </Button>
            <Button as-child class="bg-teal-800 hover:bg-teal-900">
                <Link :href="route('catalog.index')">Continue shopping</Link>
            </Button>
        </div>
    </StorefrontLayout>
</template>
