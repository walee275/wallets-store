<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { Head, Link, router } from '@inertiajs/vue3';

interface Order {
    id: number;
    number: string;
    status: string;
    total_cents: number;
    placed_at?: string | null;
}

interface Address {
    id: number;
    name: string;
    line1: string;
    city: string;
    country: string;
    is_default: boolean;
}

interface Customer {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    orders: Order[];
    addresses: Address[];
}

defineProps<{
    customer: Customer;
}>();

const { formatMoney } = useFormatMoney();

function deactivate(id: number) {
    if (confirm('Deactivate this customer?')) {
        router.patch(route('admin.customers.deactivate', id));
    }
}
</script>

<template>
    <Head :title="customer.name" />

    <AdminLayout>
        <Link :href="route('admin.customers.index')" class="text-sm text-teal-800 hover:underline">&larr; Customers</Link>

        <div class="mt-4 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ customer.name }}</h1>
                <p class="text-sm text-stone-500">{{ customer.email }}<span v-if="customer.phone"> · {{ customer.phone }}</span></p>
            </div>
            <Button variant="destructive" size="sm" @click="deactivate(customer.id)">Deactivate</Button>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-semibold">Orders</h2>
                <ul v-if="customer.orders.length" class="mt-4 divide-y divide-stone-100">
                    <li v-for="order in customer.orders" :key="order.id" class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <Link :href="route('admin.orders.show', order.id)" class="font-medium text-teal-800 hover:underline">
                                #{{ order.number }}
                            </Link>
                            <p class="text-stone-500 capitalize">{{ order.status }}</p>
                        </div>
                        <span>{{ formatMoney(order.total_cents) }}</span>
                    </li>
                </ul>
                <p v-else class="mt-4 text-sm text-stone-500">No orders.</p>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-semibold">Addresses</h2>
                <ul v-if="customer.addresses.length" class="mt-4 space-y-3 text-sm">
                    <li v-for="address in customer.addresses" :key="address.id" class="rounded-md border border-stone-100 p-3">
                        <p class="font-medium">{{ address.name }}<span v-if="address.is_default" class="ml-2 text-xs text-teal-800">Default</span></p>
                        <p class="text-stone-600">{{ address.line1 }}, {{ address.city }}, {{ address.country }}</p>
                    </li>
                </ul>
                <p v-else class="mt-4 text-sm text-stone-500">No addresses.</p>
            </section>
        </div>
    </AdminLayout>
</template>
