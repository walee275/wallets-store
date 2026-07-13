<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Parent {
    id: number;
    name: string;
}

defineProps<{
    parents: Parent[];
}>();

const form = useForm({
    name: '',
    slug: '',
    parent_id: null as number | null,
    position: 0,
    is_active: true,
});

function submit() {
    form.post(route('admin.categories.store'));
}
</script>

<template>
    <Head title="Create Category" />

    <AdminLayout>
        <Link :href="route('admin.categories.index')" class="text-sm text-teal-800 hover:underline">&larr; Categories</Link>
        <h1 class="mt-2 text-2xl font-semibold">Create category</h1>

        <form class="mt-6 max-w-md space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" required />
                <InputError :message="form.errors.name" />
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
            <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Create</Button>
        </form>
    </AdminLayout>
</template>
