<script setup lang="ts">
import PaginationLinks from '@/components/storefront/PaginationLinks.vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Order {
    id: number;
    number: string;
    status: string;
    total_cents: number;
    placed_at?: string | null;
    items_count?: number;
}

interface Paginator {
    data: Order[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

defineProps<{
    orders: Paginator;
}>();

const { formatMoney } = useFormatMoney();
</script>

<template>
    <Head title="My orders" />

    <StorefrontLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">My orders</h1>
            <nav class="flex gap-4 text-sm">
                <Link :href="route('account.addresses')" class="text-teal-800 hover:underline">Addresses</Link>
                <Link :href="route('account.wishlist')" class="text-teal-800 hover:underline">Wishlist</Link>
            </nav>
        </div>

        <div v-if="orders.data.length" class="space-y-3">
            <Link
                v-for="order in orders.data"
                :key="order.id"
                :href="route('account.orders.show', order.id)"
                class="flex flex-col gap-2 rounded-lg border border-stone-200 bg-white p-4 transition hover:border-teal-700/40 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="font-medium">Order #{{ order.number }}</p>
                    <p class="text-sm text-stone-500">
                        {{ order.placed_at ? new Date(order.placed_at).toLocaleDateString() : 'Pending' }}
                        · {{ order.status }}
                    </p>
                </div>
                <p class="font-semibold text-teal-800">{{ formatMoney(order.total_cents) }}</p>
            </Link>
        </div>

        <div v-else class="rounded-lg border border-dashed border-stone-300 bg-white px-6 py-12 text-center text-stone-500">
            You haven't placed any orders yet.
            <Link :href="route('catalog.index')" class="mt-2 block text-teal-800 hover:underline">Start shopping</Link>
        </div>

        <div class="mt-8">
            <PaginationLinks :paginator="orders" />
        </div>
    </StorefrontLayout>
</template>
