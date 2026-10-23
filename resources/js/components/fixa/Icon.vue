<script setup lang="ts">
/**
 * Ícone Material Symbols Rounded. Decorativo: o texto ao lado dá o significado.
 */
withDefaults(
    defineProps<{
        name: string;
        filled?: boolean;
        size?: number;
    }>(),
    { filled: false, size: 24 },
);
</script>

<template>
    <span
        class="icon shrink-0 select-none"
        :class="{ 'icon-filled': filled }"
        :style="{ fontSize: `${size}px` }"
        aria-hidden="true"
        >{{ name }}</span
    >
</template>
