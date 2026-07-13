<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Address {
    id: number;
    name: string;
    phone?: string | null;
    line1: string;
    line2?: string | null;
    city: string;
    state?: string | null;
    postal_code?: string | null;
    country: string;
    is_default: boolean;
}

defineProps<{
    addresses: Address[];
}>();

const showForm = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    phone: '',
    line1: '',
    line2: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'PK',
    is_default: false,
});

function resetForm() {
    form.reset();
    form.country = 'PK';
    editingId.value = null;
    showForm.value = false;
}

function startCreate() {
    resetForm();
    showForm.value = true;
}

function startEdit(address: Address) {
    editingId.value = address.id;
    form.name = address.name;
    form.phone = address.phone ?? '';
    form.line1 = address.line1;
    form.line2 = address.line2 ?? '';
    form.city = address.city;
    form.state = address.state ?? '';
    form.postal_code = address.postal_code ?? '';
    form.country = address.country;
    form.is_default = address.is_default;
    showForm.value = true;
}

function submit() {
    if (editingId.value) {
        form.put(route('account.addresses.update', editingId.value), {
            onSuccess: resetForm,
        });
    } else {
        form.post(route('account.addresses.store'), {
            onSuccess: resetForm,
        });
    }
}

function removeAddress(id: number) {
    if (confirm('Remove this address?')) {
        router.delete(route('account.addresses.destroy', id));
    }
}
</script>

<template>
    <Head title="Addresses" />

    <StorefrontLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Saved addresses</h1>
            <nav class="flex gap-4 text-sm">
                <Link :href="route('account.orders')" class="text-teal-800 hover:underline">Orders</Link>
                <Link :href="route('account.wishlist')" class="text-teal-800 hover:underline">Wishlist</Link>
            </nav>
        </div>

        <Button v-if="!showForm" class="mb-6 bg-teal-800 hover:bg-teal-900" @click="startCreate">Add address</Button>

        <form v-if="showForm" class="mb-8 space-y-4 rounded-lg border border-stone-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="font-semibold">{{ editingId ? 'Edit address' : 'New address' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2 sm:col-span-2">
                    <Label for="name">Full name</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="space-y-2">
                    <Label for="phone">Phone</Label>
                    <Input id="phone" v-model="form.phone" />
                </div>
                <div class="space-y-2">
                    <Label for="country">Country</Label>
                    <Input id="country" v-model="form.country" maxlength="2" required />
                </div>
                <div class="space-y-2 sm:col-span-2">
                    <Label for="line1">Address line 1</Label>
                    <Input id="line1" v-model="form.line1" required />
                </div>
                <div class="space-y-2 sm:col-span-2">
                    <Label for="line2">Address line 2</Label>
                    <Input id="line2" v-model="form.line2" />
                </div>
                <div class="space-y-2">
                    <Label for="city">City</Label>
                    <Input id="city" v-model="form.city" required />
                </div>
                <div class="space-y-2">
                    <Label for="state">State</Label>
                    <Input id="state" v-model="form.state" />
                </div>
                <div class="space-y-2">
                    <Label for="postal_code">Postal code</Label>
                    <Input id="postal_code" v-model="form.postal_code" />
                </div>
                <div class="flex items-center gap-2 sm:col-span-2">
                    <Checkbox id="is_default" v-model:checked="form.is_default" />
                    <Label for="is_default">Set as default</Label>
                </div>
            </div>
            <div class="flex gap-2">
                <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Save</Button>
                <Button type="button" variant="outline" @click="resetForm">Cancel</Button>
            </div>
        </form>

        <div v-if="addresses.length" class="grid gap-4 sm:grid-cols-2">
            <article v-for="address in addresses" :key="address.id" class="rounded-lg border border-stone-200 bg-white p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium">{{ address.name }}</p>
                        <span v-if="address.is_default" class="text-xs text-teal-800">Default</span>
                    </div>
                </div>
                <p class="mt-2 text-sm text-stone-600">
                    {{ address.line1 }}<br />
                    <template v-if="address.line2">{{ address.line2 }}<br /></template>
                    {{ address.city }}, {{ address.country }}
                </p>
                <div class="mt-3 flex gap-3 text-sm">
                    <button type="button" class="text-teal-800 hover:underline" @click="startEdit(address)">Edit</button>
                    <button type="button" class="text-red-600 hover:underline" @click="removeAddress(address.id)">Remove</button>
                </div>
            </article>
        </div>

        <div v-else-if="!showForm" class="rounded-lg border border-dashed border-stone-300 bg-white px-6 py-12 text-center text-stone-500">
            No saved addresses yet.
        </div>
    </StorefrontLayout>
</template>
