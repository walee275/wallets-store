<script setup lang="ts">
withDefaults(
    defineProps<{
        onCanvas?: boolean;
    }>(),
    {
        onCanvas: false,
    },
);
</script>

<template>
    <div :class="onCanvas ? 'stitch-on-canvas' : 'stitch'" role="presentation" />
</template>
