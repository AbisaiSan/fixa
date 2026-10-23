<script setup lang="ts">
/**
 * Espaço reservado enquanto o conteúdo carrega, com aria-busy e texto de status.
 */
withDefaults(defineProps<{ label: string; lines?: number }>(), { lines: 3 });
</script>

<template>
    <div role="status" aria-busy="true" class="flex flex-col gap-2.5">
        <span class="sr-only">{{ label }}</span>
        <span
            v-for="line in lines"
            :key="line"
            aria-hidden="true"
            class="h-4 animate-pulse rounded-xs bg-sf2"
            :class="line === lines && lines > 1 ? 'w-3/5' : 'w-full'"
        />
    </div>
</template>
