<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    slug: '',
    type: 'manual',
    is_featured: false,
});

function submit() {
    form.post(route('admin.collections.store'));
}
</script>

<template>
    <Head title="Create Collection" />

    <AdminLayout>
        <Link :href="route('admin.collections.index')" class="text-sm text-teal-800 hover:underline">&larr; Collections</Link>
        <h1 class="mt-2 text-2xl font-semibold">Create collection</h1>

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
                <Label for="type">Type</Label>
                <select id="type" v-model="form.type" class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                    <option value="manual">Manual</option>
                    <option value="smart">Smart</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <Checkbox id="is_featured" v-model:checked="form.is_featured" />
                <Label for="is_featured">Featured on homepage</Label>
            </div>
            <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Create</Button>
        </form>
    </AdminLayout>
</template>
