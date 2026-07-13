<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Product {
    id: number;
    title: string;
    slug: string;
}

interface Collection {
    id: number;
    name: string;
    slug: string;
    type: string;
    is_featured: boolean;
    products: Product[];
}

const props = defineProps<{
    collection: Collection;
    products: Product[];
}>();

const form = useForm({
    name: props.collection.name,
    slug: props.collection.slug,
    type: props.collection.type,
    is_featured: props.collection.is_featured,
});

const syncForm = useForm({
    product_ids: props.collection.products.map((p) => p.id),
});

const search = ref('');

function submit() {
    form.put(route('admin.collections.update', props.collection.id));
}

function syncProducts() {
    syncForm.post(route('admin.collections.sync-products', props.collection.id));
}

function toggleProduct(id: number) {
    const index = syncForm.product_ids.indexOf(id);
    if (index >= 0) syncForm.product_ids.splice(index, 1);
    else syncForm.product_ids.push(id);
}

function destroyCollection() {
    if (confirm('Delete this collection?')) {
        router.delete(route('admin.collections.destroy', props.collection.id));
    }
}

const filteredProducts = computed(() =>
    props.products.filter((p) => p.title.toLowerCase().includes(search.value.toLowerCase())),
);
</script>

<template>
    <Head :title="`Edit ${collection.name}`" />

    <AdminLayout>
        <Link :href="route('admin.collections.index')" class="text-sm text-teal-800 hover:underline">&larr; Collections</Link>
        <h1 class="mt-2 text-2xl font-semibold">Edit collection</h1>

        <form class="mt-6 max-w-md space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" required />
            </div>
            <div class="space-y-2">
                <Label for="slug">Slug</Label>
                <Input id="slug" v-model="form.slug" required />
            </div>
            <div class="space-y-2">
                <Label for="type">Type</Label>
                <select id="type" v-model="form.type" class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                    <option value="manual">Manual</option>
                    <option value="smart">Smart</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <Checkbox id="is_featured" v-model:checked="form.is_featured" />
                <Label for="is_featured">Featured</Label>
            </div>
            <div class="flex gap-3">
                <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Save</Button>
                <Button type="button" variant="destructive" @click="destroyCollection">Delete</Button>
            </div>
        </form>

        <section v-if="collection.type === 'manual'" class="mt-10 max-w-lg">
            <h2 class="font-semibold">Products in collection</h2>
            <Input v-model="search" placeholder="Filter products..." class="mt-3" />
            <form class="mt-4 space-y-2" @submit.prevent="syncProducts">
                <label
                    v-for="product in filteredProducts"
                    :key="product.id"
                    class="flex cursor-pointer items-center gap-2 rounded-md border border-stone-200 px-3 py-2 text-sm"
                >
                    <input type="checkbox" :checked="syncForm.product_ids.includes(product.id)" @change="toggleProduct(product.id)" />
                    {{ product.title }}
                </label>
                <Button type="submit" variant="outline" class="mt-3" :disabled="syncForm.processing">Sync products</Button>
            </form>
        </section>
    </AdminLayout>
</template>
