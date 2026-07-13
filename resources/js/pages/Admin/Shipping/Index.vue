<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { Head, router, useForm } from '@inertiajs/vue3';

interface ShippingRate {
    id: number;
    name: string;
    price_cents: number;
    is_active: boolean;
}

interface ShippingZone {
    id: number;
    name: string;
    countries_json: string[];
    is_active: boolean;
    rates: ShippingRate[];
}

defineProps<{
    zones: ShippingZone[];
}>();

const { formatMoney } = useFormatMoney();

const zoneForm = useForm({
    name: '',
    countries_json: ['PK'],
    is_active: true,
});

const rateForm = useForm({
    zone_id: null as number | null,
    name: '',
    price: 0,
    is_active: true,
});

function createZone() {
    zoneForm.post(route('admin.shipping.zones.store'), {
        preserveScroll: true,
        onSuccess: () => zoneForm.reset(),
    });
}

function createRate() {
    rateForm.post(route('admin.shipping.rates.store'), {
        preserveScroll: true,
        onSuccess: () => rateForm.reset(),
    });
}

function deleteZone(id: number) {
    if (confirm('Delete this zone and its rates?')) {
        router.delete(route('admin.shipping.zones.destroy', id));
    }
}

function deleteRate(id: number) {
    if (confirm('Delete this rate?')) {
        router.delete(route('admin.shipping.rates.destroy', id));
    }
}
</script>

<template>
    <Head title="Shipping" />

    <AdminLayout>
        <h1 class="mb-6 text-2xl font-semibold">Shipping</h1>

        <div class="grid gap-8 lg:grid-cols-2">
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-semibold">Add zone</h2>
                <form class="mt-4 space-y-3" @submit.prevent="createZone">
                    <div class="space-y-2">
                        <Label for="zone_name">Name</Label>
                        <Input id="zone_name" v-model="zoneForm.name" required />
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox id="zone_active" v-model:checked="zoneForm.is_active" />
                        <Label for="zone_active">Active</Label>
                    </div>
                    <Button type="submit" variant="outline" :disabled="zoneForm.processing">Add zone</Button>
                </form>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-semibold">Add rate</h2>
                <form class="mt-4 space-y-3" @submit.prevent="createRate">
                    <div class="space-y-2">
                        <Label for="rate_zone">Zone</Label>
                        <select id="rate_zone" v-model="rateForm.zone_id" class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm" required>
                            <option :value="null" disabled>Select zone</option>
                            <option v-for="zone in zones" :key="zone.id" :value="zone.id">{{ zone.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="rate_name">Name</Label>
                        <Input id="rate_name" v-model="rateForm.name" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="rate_price">Price</Label>
                        <Input id="rate_price" v-model.number="rateForm.price" type="number" min="0" step="0.01" required />
                    </div>
                    <Button type="submit" variant="outline" :disabled="rateForm.processing">Add rate</Button>
                </form>
            </section>
        </div>

        <div class="mt-8 space-y-4">
            <article v-for="zone in zones" :key="zone.id" class="rounded-lg border border-stone-200 bg-white p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-semibold">{{ zone.name }}</h2>
                        <p class="text-sm text-stone-500">{{ zone.countries_json.join(', ') }} · {{ zone.is_active ? 'Active' : 'Inactive' }}</p>
                    </div>
                    <button type="button" class="text-sm text-red-600 hover:underline" @click="deleteZone(zone.id)">Delete zone</button>
                </div>
                <table v-if="zone.rates.length" class="mt-4 w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-200 text-stone-500">
                            <th class="py-2 text-left font-medium">Rate</th>
                            <th class="py-2 text-left font-medium">Price</th>
                            <th class="py-2 text-left font-medium">Active</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="rate in zone.rates" :key="rate.id" class="border-b border-stone-100">
                            <td class="py-2">{{ rate.name }}</td>
                            <td class="py-2">{{ formatMoney(rate.price_cents) }}</td>
                            <td class="py-2">{{ rate.is_active ? 'Yes' : 'No' }}</td>
                            <td class="py-2 text-right">
                                <button type="button" class="text-red-600 hover:underline" @click="deleteRate(rate.id)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="mt-4 text-sm text-stone-500">No rates for this zone.</p>
            </article>
        </div>
    </AdminLayout>
</template>
