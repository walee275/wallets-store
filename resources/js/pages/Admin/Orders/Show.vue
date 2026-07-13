<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface OrderItem {
    id: number;
    product_title: string;
    quantity: number;
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
    notes?: string | null;
    subtotal_cents: number;
    discount_cents: number;
    shipping_cents: number;
    tax_cents: number;
    total_cents: number;
    placed_at?: string | null;
    shipping_address_json?: Record<string, string> | null;
    user?: { name: string; email: string; phone?: string | null };
    items: OrderItem[];
    payments?: { status: string; amount_cents: number }[];
    statusHistories?: StatusHistory[];
    shippingRate?: { name: string; zone?: { name: string } } | null;
}

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    order: Order;
    statuses: StatusOption[];
}>();

const { formatMoney } = useFormatMoney();

const statusForm = useForm({
    status: props.order.status,
    note: '',
});

const noteForm = useForm({
    note: '',
});

function updateStatus() {
    statusForm.patch(route('admin.orders.update-status', props.order.id), {
        preserveScroll: true,
        onSuccess: () => statusForm.reset('note'),
    });
}

function addNote() {
    noteForm.post(route('admin.orders.add-note', props.order.id), {
        preserveScroll: true,
        onSuccess: () => noteForm.reset(),
    });
}
</script>

<template>
    <Head :title="`Order ${order.number}`" />

    <AdminLayout>
        <Link :href="route('admin.orders.index')" class="text-sm text-teal-800 hover:underline">&larr; Orders</Link>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Order #{{ order.number }}</h1>
                <p class="text-sm text-stone-500">
                    {{ order.placed_at ? new Date(order.placed_at).toLocaleString() : 'Not placed' }}
                    · {{ order.email }}
                </p>
            </div>
            <span class="inline-flex rounded-full bg-stone-100 px-3 py-1 text-sm capitalize">{{ order.status }}</span>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_340px]">
            <div class="space-y-6">
                <section class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-semibold">Line items</h2>
                    <table class="mt-4 w-full text-sm">
                        <tbody>
                            <tr v-for="item in order.items" :key="item.id" class="border-b border-stone-100">
                                <td class="py-2">{{ item.product_title }} × {{ item.quantity }}</td>
                                <td class="py-2 text-right">{{ formatMoney(item.line_total_cents) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section v-if="order.notes" class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-semibold">Notes</h2>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-stone-600">{{ order.notes }}</p>
                </section>

                <section v-if="order.statusHistories?.length" class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-semibold">Status history</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li v-for="entry in order.statusHistories" :key="entry.id">
                            <span class="capitalize">{{ entry.to_status }}</span>
                            <span class="text-stone-500"> — {{ new Date(entry.created_at).toLocaleString() }}</span>
                            <span v-if="entry.changedBy" class="text-stone-400"> by {{ entry.changedBy.name }}</span>
                        </li>
                    </ul>
                </section>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-semibold">Summary</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ formatMoney(order.subtotal_cents) }}</dd></div>
                        <div v-if="order.discount_cents" class="flex justify-between"><dt>Discount</dt><dd>-{{ formatMoney(order.discount_cents) }}</dd></div>
                        <div class="flex justify-between"><dt>Shipping</dt><dd>{{ formatMoney(order.shipping_cents) }}</dd></div>
                        <div class="flex justify-between"><dt>Tax</dt><dd>{{ formatMoney(order.tax_cents) }}</dd></div>
                        <div class="flex justify-between border-t border-stone-200 pt-2 font-semibold"><dt>Total</dt><dd>{{ formatMoney(order.total_cents) }}</dd></div>
                    </dl>
                </div>

                <form class="rounded-lg border border-stone-200 bg-white p-5" @submit.prevent="updateStatus">
                    <h2 class="font-semibold">Update status</h2>
                    <div class="mt-3 space-y-3">
                        <select v-model="statusForm.status" class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                        <Input v-model="statusForm.note" placeholder="Note (optional)" />
                        <InputError :message="statusForm.errors.status" />
                        <Button type="submit" class="w-full bg-teal-800 hover:bg-teal-900" :disabled="statusForm.processing">Update</Button>
                    </div>
                </form>

                <form class="rounded-lg border border-stone-200 bg-white p-5" @submit.prevent="addNote">
                    <h2 class="font-semibold">Add note</h2>
                    <div class="mt-3 space-y-3">
                        <textarea v-model="noteForm.note" rows="3" class="flex w-full rounded-md border border-input px-3 py-2 text-sm" required />
                        <Button type="submit" variant="outline" class="w-full" :disabled="noteForm.processing">Add note</Button>
                    </div>
                </form>
            </aside>
        </div>
    </AdminLayout>
</template>
