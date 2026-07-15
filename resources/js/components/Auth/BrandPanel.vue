<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const storeName = computed(() => {
    const store = page.props.store as { name?: string } | undefined;
    return store?.name ?? (page.props.name as string) ?? 'Commerce';
});
</script>

<template>
    <aside
        class="relative flex min-h-[220px] flex-col justify-between overflow-hidden bg-[radial-gradient(ellipse_700px_500px_at_20%_15%,rgba(140,90,43,0.35),transparent_60%),radial-gradient(ellipse_600px_500px_at_85%_85%,rgba(92,31,34,0.3),transparent_55%),repeating-radial-gradient(circle_at_50%_50%,rgba(0,0,0,0.06)_0,rgba(0,0,0,0.06)_1px,transparent_2px,transparent_4px),linear-gradient(160deg,#241811_0%,#2E2016_45%,#201509_100%)] p-9 text-canvas min-[860px]:min-h-screen min-[860px]:p-[52px]"
        aria-label="Brand"
    >
        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(0deg,rgba(20,13,9,0.5)_0%,rgba(20,13,9,0)_40%)]" />
        <div class="vstitch hidden min-[860px]:block" aria-hidden="true" />

        <div class="relative z-[2]">
            <Link
                :href="route('home')"
                class="font-display text-[22px] font-[450] tracking-[0.5px] text-canvas outline-none focus-visible:ring-2 focus-visible:ring-brass focus-visible:ring-offset-2 focus-visible:ring-offset-espresso"
            >
                {{ storeName }}
            </Link>
        </div>

        <div class="relative z-[2] mt-8 min-[860px]:mt-0">
            <p class="max-w-[420px] font-display text-xl font-[450] leading-[1.35] min-[860px]:text-[30px]">
                <span class="text-brass">“</span>Full-grain leather doesn't wear out — it wears in.<span class="text-brass">”</span>
            </p>
            <p class="mt-5 font-mono text-[11px] uppercase tracking-[1.5px] text-[#B7A98C]">
                From the Workshop Floor, Islamabad
            </p>
        </div>
    </aside>
</template>
