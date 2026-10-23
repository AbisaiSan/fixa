<script setup lang="ts">
withDefaults(
    defineProps<{
        tone?: 'default' | 'hero';
        as?: string;
    }>(),
    { tone: 'default', as: 'div' },
);
</script>

<template>
    <component
        :is="as"
        class="rounded-l border border-line p-5 shadow-sh1"
        :class="tone === 'hero' ? 'bg-(image:--hero) p-6' : 'bg-sf'"
    >
        <slot />
    </component>
</template>
