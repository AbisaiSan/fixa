<script setup lang="ts">
import Icon from './Icon.vue';

/**
 * Aviso persistente no topo do conteúdo. O título vem em negrito, o resto no slot.
 */
withDefaults(
    defineProps<{
        tone?: 'neutral' | 'warning' | 'info' | 'error';
        icon: string;
        title: string;
    }>(),
    { tone: 'neutral' },
);

const tones = {
    neutral: { box: 'bg-sf2', icon: 'text-ink2' },
    warning: { box: 'bg-ambt', icon: 'text-ambd' },
    info: { box: 'bg-infot', icon: 'text-infod' },
    error: { box: 'bg-errt', icon: 'text-errd' },
} as const;
</script>

<template>
    <div
        :role="tone === 'error' ? 'alert' : undefined"
        class="flex gap-2.5 rounded-m px-3.5 py-3 text-sm leading-normal"
        :class="tones[tone].box"
    >
        <Icon :name="icon" :class="tones[tone].icon" />
        <span
            ><strong>{{ title }}</strong> <slot
        /></span>
    </div>
</template>
