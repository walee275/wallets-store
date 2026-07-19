<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut, Menu, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const page = usePage();
const sidebarOpen = ref(false);

const flash = computed(() => page.props.flash as { success?: string | null; error?: string | null });
const authUser = computed(() => (page.props.auth as { user?: { name?: string; email?: string } | null })?.user ?? null);

const navItems = [
    { label: 'Dashboard', route: 'admin.dashboard' },
    { label: 'Products', route: 'admin.products.index' },
    { label: 'Orders', route: 'admin.orders.index' },
    { label: 'Customers', route: 'admin.customers.index' },
    { label: 'Categories', route: 'admin.categories.index' },
    { label: 'Collections', route: 'admin.collections.index' },
    { label: 'Discounts', route: 'admin.discounts.index' },
    { label: 'Inventory', route: 'admin.inventory.index' },
    { label: 'Shipping', route: 'admin.shipping.index' },
    { label: 'Payment Methods', route: 'admin.payment-methods.index' },
    { label: 'Reviews', route: 'admin.reviews.index' },
    { label: 'Pages', route: 'admin.pages.index' },
    { label: 'Settings', route: 'admin.settings.edit' },
];

function isActive(routeName: string): boolean {
    try {
        const current = route().current() ?? '';
        return current === routeName || current.startsWith(`${routeName}.`);
    } catch {
        return false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-[#faf8f5] text-[#1a1a1a]">
        <div class="flex min-h-screen">
            <aside
                class="fixed inset-y-0 left-0 z-50 flex w-64 transform flex-col border-r border-stone-200 bg-white transition-transform lg:static lg:translate-x-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <div class="flex h-14 shrink-0 items-center justify-between border-b border-stone-200 px-4">
                    <Link :href="route('admin.dashboard')" class="font-semibold text-teal-800">Admin</Link>
                    <button type="button" class="rounded-md p-1 lg:hidden" @click="sidebarOpen = false">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <nav class="flex-1 space-y-0.5 overflow-y-auto p-3">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        class="block rounded-md px-3 py-2 text-sm transition"
                        :class="isActive(item.route) ? 'bg-teal-50 font-medium text-teal-900' : 'text-stone-600 hover:bg-stone-50'"
                        @click="sidebarOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
                <div class="shrink-0 space-y-2 border-t border-stone-200 p-3">
                    <div v-if="authUser" class="px-3 py-1">
                        <p class="truncate text-sm font-medium text-stone-800">{{ authUser.name }}</p>
                        <p v-if="authUser.email" class="truncate text-xs text-stone-500">{{ authUser.email }}</p>
                    </div>
                    <Link
                        :href="route('home')"
                        class="block rounded-md px-3 py-2 text-sm text-stone-500 transition hover:bg-stone-50 hover:text-teal-800"
                        @click="sidebarOpen = false"
                    >
                        View storefront
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-stone-600 transition hover:bg-stone-50 hover:text-red-700"
                        @click="sidebarOpen = false"
                    >
                        <LogOut class="h-4 w-4" />
                        Log out
                    </Link>
                </div>
            </aside>

            <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/30 lg:hidden" @click="sidebarOpen = false" />

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex h-14 items-center gap-3 border-b border-stone-200 bg-white px-4 lg:hidden">
                    <button type="button" class="rounded-md p-2 hover:bg-stone-100" @click="sidebarOpen = true">
                        <Menu class="h-5 w-5" />
                    </button>
                    <span class="font-medium">Admin</span>
                </header>

                <div v-if="flash?.success" class="border-b border-teal-200 bg-teal-50 px-4 py-2 text-sm text-teal-900">
                    {{ flash.success }}
                </div>
                <div v-if="flash?.error" class="border-b border-red-200 bg-red-50 px-4 py-2 text-sm text-red-900">
                    {{ flash.error }}
                </div>

                <main class="flex-1 p-4 lg:p-6">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
