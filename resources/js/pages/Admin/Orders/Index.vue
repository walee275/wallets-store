<script setup lang="ts">
import PaginationLinks from '@/components/storefront/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Order {
    id: number;
    number: string;
    status: string;
    total_cents: number;
    placed_at?: string | null;
    user?: { name: string; email: string };
}

interface StatusOption {
    value: string;
    label: string;
}

interface Paginator {
    data: Order[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    orders: Paginator;
    filters: { status: string };
    statuses: StatusOption[];
}>();

const { formatMoney } = useFormatMoney();
const status = ref(props.filters.status ?? '');

function applyFilter() {
    router.get(route('admin.orders.index'), { status: status.value || undefined }, { preserveState: true });
}
</script>

<template>
    <Head title="Orders" />

    <AdminLayout>
        <h1 class="mb-6 text-2xl font-semibold">Orders</h1>

        <form class="mb-6 flex gap-2" @submit.prevent="applyFilter">
            <select v-model="status" class="flex h-9 rounded-md border border-input bg-background px-3 text-sm">
                <option value="">All statuses</option>
                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
            <Button type="submit" variant="outline">Filter</Button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-200 bg-stone-50 text-stone-500">
                        <th class="px-4 py-3 font-medium">Order</th>
                        <th class="px-4 py-3 font-medium">Customer</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in orders.data" :key="order.id" class="border-b border-stone-100">
                        <td class="px-4 py-3 font-medium">#{{ order.number }}</td>
                        <td class="px-4 py-3">
                            <span v-if="order.user">{{ order.user.name }}</span>
                            <span v-else class="text-stone-400">Guest</span>
                        </td>
                        <td class="px-4 py-3 capitalize">{{ order.status }}</td>
                        <td class="px-4 py-3">{{ formatMoney(order.total_cents) }}</td>
                        <td class="px-4 py-3">{{ order.placed_at ? new Date(order.placed_at).toLocaleDateString() : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.orders.show', order.id)" class="text-teal-800 hover:underline">View</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6"><PaginationLinks :paginator="orders" /></div>
    </AdminLayout>
</template>
