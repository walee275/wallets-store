<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const page = usePage();
const storeName = computed(() => {
    const store = page.props.store as { branding?: { name?: string }; name?: string } | undefined;
    return store?.branding?.name ?? store?.name ?? (page.props.name as string) ?? 'Commerce';
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const fieldErrorClass = 'mt-2 font-mono text-[11px] tracking-[0.3px] text-oxblood';
</script>

<template>
    <AuthLayout>
        <Head :title="`Sign In — ${storeName}`" />

        <p class="mb-3.5 font-mono text-[11px] uppercase tracking-[2.5px] text-atelier-stone">Account Access</p>
        <h1 class="mb-2.5 font-display text-[34px] font-[450] text-ink">Welcome back</h1>
        <p class="mb-9 text-sm leading-[22px] text-atelier-stone">
            Sign in to track orders, save addresses, and reorder your essentials. New here?
            <Link
                :href="route('register')"
                class="border-b border-oxblood text-oxblood outline-none focus-visible:ring-2 focus-visible:ring-cognac focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
            >
                Create an account
            </Link>
        </p>

        <div v-if="status" class="mb-6 font-mono text-[11px] tracking-[0.3px] text-cognac" role="status">
            {{ status }}
        </div>

        <form class="flex flex-col" @submit.prevent="submit">
            <div class="mb-[22px]">
                <label for="email" class="mb-2.5 block font-mono text-[11px] uppercase tracking-[1.5px] text-ink">
                    Email Address
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    tabindex="1"
                    placeholder="you@example.com"
                    class="w-full border border-hairline bg-transparent px-3.5 py-[13px] font-sans text-[15px] text-ink outline-none transition-[border-color] duration-150 placeholder:text-atelier-stone/70 focus:border-cognac focus-visible:ring-2 focus-visible:ring-cognac/30 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
                />
                <InputError :message="form.errors.email" :class="fieldErrorClass" />
            </div>

            <div class="mb-[22px]">
                <label for="password" class="mb-2.5 block font-mono text-[11px] uppercase tracking-[1.5px] text-ink">
                    Password
                </label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="current-password"
                    tabindex="2"
                    placeholder="••••••••••"
                    class="w-full border border-hairline bg-transparent px-3.5 py-[13px] font-sans text-[15px] text-ink outline-none transition-[border-color] duration-150 placeholder:text-atelier-stone/70 focus:border-cognac focus-visible:ring-2 focus-visible:ring-cognac/30 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
                />
                <InputError :message="form.errors.password" :class="fieldErrorClass" />
            </div>

            <div class="mb-[30px] flex items-center justify-between text-[13px]">
                <label for="remember" class="flex items-center gap-2.5 text-atelier-stone">
                    <input
                        id="remember"
                        v-model="form.remember"
                        type="checkbox"
                        tabindex="3"
                        class="h-[15px] w-[15px] accent-cognac outline-none focus-visible:ring-2 focus-visible:ring-cognac focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
                    />
                    Remember me
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    tabindex="4"
                    class="border-b border-oxblood pb-px text-oxblood outline-none focus-visible:ring-2 focus-visible:ring-cognac focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
                >
                    Forgot password?
                </Link>
            </div>

            <button
                type="submit"
                tabindex="5"
                :disabled="form.processing"
                class="w-full bg-cognac px-4 py-4 font-sans text-[13px] font-semibold uppercase tracking-[1.5px] text-[#F5EFE3] transition-colors duration-200 outline-none hover:bg-[#7A4B22] focus-visible:ring-2 focus-visible:ring-cognac focus-visible:ring-offset-2 focus-visible:ring-offset-canvas disabled:cursor-not-allowed disabled:opacity-60"
            >
                {{ form.processing ? 'Signing in…' : 'Sign In' }}
            </button>
        </form>

        <div
            class="my-[34px] flex items-center gap-3.5 font-mono text-[10px] uppercase tracking-[1.5px] text-atelier-stone before:h-px before:flex-1 before:bg-hairline after:h-px after:flex-1 after:bg-hairline"
        >
            New to {{ storeName }}
        </div>

        <p class="text-center text-[13px] text-atelier-stone">
            Don't have an account yet?
            <Link
                :href="route('register')"
                tabindex="6"
                class="border-b border-ink text-ink outline-none focus-visible:ring-2 focus-visible:ring-cognac focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
            >
                Create one
            </Link>
        </p>
    </AuthLayout>
</template>
