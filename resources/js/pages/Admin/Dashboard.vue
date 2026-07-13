<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { Head, Link } from '@inertiajs/vue3';

interface TopProduct {
    product_title: string;
    variant_id: number;
    total_quantity: number;
    total_revenue_cents: number;
}

interface Metrics {
    revenue_cents: number;
    order_count: number;
    average_order_value: number;
    top_products: TopProduct[];
}

defineProps<{
    metrics: Metrics;
    low_stock_variants_count: number;
}>();

const { formatMoney } = useFormatMoney();
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <h1 class="mb-6 text-2xl font-semibold">Dashboard</h1>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-stone-200 bg-white p-5">
                <p class="text-sm text-stone-500">Revenue</p>
                <p class="mt-1 text-2xl font-semibold">{{ formatMoney(metrics.revenue_cents) }}</p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5">
                <p class="text-sm text-stone-500">Orders</p>
                <p class="mt-1 text-2xl font-semibold">{{ metrics.order_count }}</p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5">
                <p class="text-sm text-stone-500">Avg. order value</p>
                <p class="mt-1 text-2xl font-semibold">{{ formatMoney(Math.round(metrics.average_order_value)) }}</p>
            </div>
            <Link :href="route('admin.inventory.index')" class="rounded-lg border border-stone-200 bg-white p-5 transition hover:border-teal-700/40">
                <p class="text-sm text-stone-500">Low stock variants</p>
                <p class="mt-1 text-2xl font-semibold" :class="low_stock_variants_count ? 'text-amber-700' : ''">
                    {{ low_stock_variants_count }}
                </p>
            </Link>
        </div>

        <section class="mt-8 rounded-lg border border-stone-200 bg-white p-5">
            <h2 class="font-semibold">Top products</h2>
            <div v-if="metrics.top_products?.length" class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-stone-200 text-stone-500">
                            <th class="pb-2 pr-4 font-medium">Product</th>
                            <th class="pb-2 pr-4 font-medium">Qty sold</th>
                            <th class="pb-2 font-medium">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in metrics.top_products" :key="item.variant_id" class="border-b border-stone-100">
                            <td class="py-3 pr-4">{{ item.product_title }}</td>
                            <td class="py-3 pr-4">{{ item.total_quantity }}</td>
                            <td class="py-3">{{ formatMoney(item.total_revenue_cents) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="mt-4 text-sm text-stone-500">No sales data yet.</p>
        </section>
    </AdminLayout>
</template>
