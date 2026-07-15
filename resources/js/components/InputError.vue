<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    message?: string;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div v-show="message">
        <p :class="cn('text-sm text-red-600 dark:text-red-500', props.class)">
            {{ message }}
        </p>
    </div>
</template>
