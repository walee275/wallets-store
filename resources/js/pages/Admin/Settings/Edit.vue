<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

interface Collection {
    id: number;
    name: string;
}

interface TaxMode {
    value: string;
    label: string;
}

interface Settings {
    homepage_banner_url?: string | null;
    featured_collection_id?: number | null;
    store_currency: string;
    tax_mode: string;
}

const props = defineProps<{
    settings: Settings;
    collections: Collection[];
    taxModes: TaxMode[];
}>();

const form = useForm({
    homepage_banner_url: props.settings.homepage_banner_url ?? '',
    featured_collection_id: props.settings.featured_collection_id,
    store_currency: props.settings.store_currency,
    tax_mode: props.settings.tax_mode,
});

function submit() {
    form.put(route('admin.settings.update'));
}
</script>

<template>
    <Head title="Settings" />

    <AdminLayout>
        <h1 class="mb-6 text-2xl font-semibold">Store settings</h1>

        <form class="max-w-md space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="homepage_banner_url">Homepage banner URL</Label>
                <Input id="homepage_banner_url" v-model="form.homepage_banner_url" type="url" />
            </div>
            <div class="space-y-2">
                <Label for="featured_collection_id">Featured collection</Label>
                <select
                    id="featured_collection_id"
                    v-model="form.featured_collection_id"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option :value="null">None</option>
                    <option v-for="collection in collections" :key="collection.id" :value="collection.id">{{ collection.name }}</option>
                </select>
            </div>
            <div class="space-y-2">
                <Label for="store_currency">Currency</Label>
                <Input id="store_currency" v-model="form.store_currency" maxlength="3" required />
            </div>
            <div class="space-y-2">
                <Label for="tax_mode">Tax mode</Label>
                <select id="tax_mode" v-model="form.tax_mode" class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                    <option v-for="mode in taxModes" :key="mode.value" :value="mode.value">{{ mode.label }}</option>
                </select>
            </div>
            <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Save settings</Button>
        </form>
    </AdminLayout>
</template>
