<script setup lang="ts">
export interface SettingsNavItem {
    id: string;
    label: string;
}

defineProps<{
    items: SettingsNavItem[];
    activeId: string;
}>();

const emit = defineEmits<{
    select: [id: string];
}>();
</script>

<template>
    <aside class="border-b border-stone-200 bg-white lg:sticky lg:top-0 lg:h-[calc(100vh-3.5rem)] lg:overflow-y-auto lg:border-b-0 lg:border-r">
        <div class="px-4 pb-3 pt-5 text-[11px] font-semibold uppercase tracking-[0.6px] text-teal-900">Store Settings</div>
        <nav class="space-y-0.5 px-3 pb-4">
            <button
                v-for="item in items"
                :key="item.id"
                type="button"
                class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-[13.5px] font-medium transition-colors"
                :class="
                    activeId === item.id
                        ? 'bg-teal-50 text-teal-900'
                        : 'text-stone-600 hover:bg-stone-100'
                "
                @click="emit('select', item.id)"
            >
                <span
                    class="h-1.5 w-1.5 shrink-0 rounded-full"
                    :class="activeId === item.id ? 'bg-teal-900' : 'bg-stone-300'"
                />
                {{ item.label }}
            </button>
        </nav>
    </aside>
</template>
