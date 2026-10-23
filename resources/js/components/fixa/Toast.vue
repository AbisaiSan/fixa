<script setup lang="ts">
import Icon from './Icon.vue';

/**
 * Aviso rápido e não bloqueante, anunciado por leitores de tela (role="status").
 */
withDefaults(
    defineProps<{
        tone?: 'success' | 'info' | 'error';
        icon?: string;
    }>(),
    { tone: 'success', icon: undefined },
);

const defaults = {
    success: { icon: 'check_circle', color: 'text-okd' },
    info: { icon: 'flag', color: 'text-infod' },
    error: { icon: 'error', color: 'text-errd' },
} as const;
</script>

<template>
    <div
        role="status"
        class="flex items-center gap-2.5 rounded-2xl border border-line bg-sf px-4 py-3 text-[14.5px] font-semibold shadow-sh2"
    >
        <Icon
            :name="icon ?? defaults[tone].icon"
            filled
            :class="defaults[tone].color"
        />
        <span><slot /></span>
    </div>
</template>
