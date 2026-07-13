<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

interface Category {
    id: number;
    name: string;
    slug: string;
    parent_id?: number | null;
    position: number;
    is_active: boolean;
}

interface Parent {
    id: number;
    name: string;
}

const props = defineProps<{
    category: Category;
    parents: Parent[];
}>();

const form = useForm({
    name: props.category.name,
    slug: props.category.slug,
    parent_id: props.category.parent_id ?? null,
    position: props.category.position,
    is_active: props.category.is_active,
});

function submit() {
    form.put(route('admin.categories.update', props.category.id));
}

function destroyCategory() {
    if (confirm('Delete this category?')) {
        router.delete(route('admin.categories.destroy', props.category.id));
    }
}
</script>

<template>
    <Head :title="`Edit ${category.name}`" />

    <AdminLayout>
        <Link :href="route('admin.categories.index')" class="text-sm text-teal-800 hover:underline">&larr; Categories</Link>
        <h1 class="mt-2 text-2xl font-semibold">Edit category</h1>

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
                <Label for="parent_id">Parent</Label>
                <select id="parent_id" v-model="form.parent_id" class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                    <option :value="null">None</option>
                    <option v-for="parent in parents" :key="parent.id" :value="parent.id">{{ parent.name }}</option>
                </select>
            </div>
            <div class="space-y-2">
                <Label for="position">Position</Label>
                <Input id="position" v-model.number="form.position" type="number" min="0" />
            </div>
            <div class="flex items-center gap-2">
                <Checkbox id="is_active" v-model:checked="form.is_active" />
                <Label for="is_active">Active</Label>
            </div>
            <div class="flex gap-3">
                <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Save</Button>
                <Button type="button" variant="destructive" @click="destroyCategory">Delete</Button>
            </div>
        </form>
    </AdminLayout>
</template>
