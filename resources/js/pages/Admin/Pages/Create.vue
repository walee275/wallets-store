<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    slug: '',
    body: '',
    seo_title: '',
    seo_description: '',
    is_published: true,
});

function submit() {
    form.post(route('admin.pages.store'));
}
</script>

<template>
    <Head title="Create Page" />

    <AdminLayout>
        <Link :href="route('admin.pages.index')" class="text-sm text-teal-800 hover:underline">&larr; Pages</Link>
        <h1 class="mt-2 text-2xl font-semibold">Create page</h1>

        <form class="mt-6 max-w-2xl space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="title">Title</Label>
                <Input id="title" v-model="form.title" required />
                <InputError :message="form.errors.title" />
            </div>
            <div class="space-y-2">
                <Label for="slug">Slug</Label>
                <Input id="slug" v-model="form.slug" required />
            </div>
            <div class="space-y-2">
                <Label for="body">Body (HTML)</Label>
                <textarea id="body" v-model="form.body" rows="10" class="flex w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm" />
            </div>
            <div class="space-y-2">
                <Label for="seo_title">SEO title</Label>
                <Input id="seo_title" v-model="form.seo_title" />
            </div>
            <div class="space-y-2">
                <Label for="seo_description">SEO description</Label>
                <Input id="seo_description" v-model="form.seo_description" />
            </div>
            <div class="flex items-center gap-2">
                <Checkbox id="is_published" v-model:checked="form.is_published" />
                <Label for="is_published">Published</Label>
            </div>
            <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Create</Button>
        </form>
    </AdminLayout>
</template>
