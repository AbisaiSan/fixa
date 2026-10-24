<script setup lang="ts">
import { computed } from 'vue';

/**
 * Anel da meta do dia (J5-F1, protótipo tela 3). O número no centro e o
 * rótulo dizem o progresso; o anel é só reforço visual. --pri só no anel.
 */
const props = withDefaults(
    defineProps<{
        percent: number;
        label: string;
        size?: number;
    }>(),
    { size: 116 },
);

const radius = 48;
const circumference = 2 * Math.PI * radius;
const offset = computed(
    () => circumference * (1 - Math.min(100, Math.max(0, props.percent)) / 100),
);
</script>

<template>
    <div
        role="img"
        :aria-label="label"
        class="relative shrink-0"
        :style="{ width: `${size}px`, height: `${size}px` }"
    >
        <svg
            viewBox="0 0 116 116"
            class="block size-full -rotate-90"
            aria-hidden="true"
        >
            <circle
                cx="58"
                cy="58"
                :r="radius"
                class="fill-none stroke-sf2"
                stroke-width="12"
            />
            <circle
                cx="58"
                cy="58"
                :r="radius"
                class="fill-none stroke-pri motion-safe:transition-[stroke-dashoffset] motion-safe:duration-700 motion-safe:ease-out"
                stroke-width="12"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="offset"
            />
        </svg>
        <div
            class="absolute inset-0 grid place-items-center text-center"
            aria-hidden="true"
        >
            <slot />
        </div>
    </div>
</template>
