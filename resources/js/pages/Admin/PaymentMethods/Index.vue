<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';

interface PaymentMethod {
    id: number;
    driver: string;
    name: string;
    is_enabled: boolean;
    sort_order: number;
}

defineProps<{
    paymentMethods: PaymentMethod[];
}>();

function toggle(method: PaymentMethod) {
    router.patch(route('admin.payment-methods.toggle', method.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Payment Methods" />

    <AdminLayout>
        <h1 class="mb-6 text-2xl font-semibold">Payment methods</h1>

        <div class="space-y-3">
            <article
                v-for="method in paymentMethods"
                :key="method.id"
                class="flex items-center justify-between rounded-lg border border-stone-200 bg-white px-5 py-4"
            >
                <div>
                    <p class="font-medium">{{ method.name }}</p>
                    <p class="text-sm text-stone-500">{{ method.driver }}</p>
                </div>
                <button
                    type="button"
                    role="switch"
                    :aria-checked="method.is_enabled"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors"
                    :class="method.is_enabled ? 'bg-teal-800' : 'bg-stone-200'"
                    @click="toggle(method)"
                >
                    <span
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition"
                        :class="method.is_enabled ? 'translate-x-5' : 'translate-x-0'"
                    />
                </button>
            </article>
        </div>
    </AdminLayout>
</template>
