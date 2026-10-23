<script setup lang="ts">
import { computed } from 'vue';
import { t } from '@/i18n';
import Icon from './Icon.vue';

/**
 * Topo das sessões em modo foco (J3-F1): sair, progresso e contador.
 * O slot mostra extras, como o tempo do modo corrida.
 */
const props = defineProps<{ current: number; total: number }>();

defineEmits<{ exit: [] }>();

const percent = computed(() =>
    props.total === 0 ? 0 : Math.round((props.current / props.total) * 100),
);
</script>

<template>
    <div class="flex items-center gap-3">
        <button
            type="button"
            class="grid size-11 shrink-0 place-items-center rounded-s text-ink2 hover:bg-sf2"
            :aria-label="t('flashcards.exit')"
            @click="$emit('exit')"
        >
            <Icon name="close" />
        </button>
        <div
            role="progressbar"
            :aria-label="t('flashcards.progress')"
            aria-valuemin="0"
            :aria-valuemax="total"
            :aria-valuenow="current"
            class="h-2.5 flex-1 overflow-hidden rounded-full bg-sf2"
        >
            <div
                class="h-full rounded-full bg-pri transition-[width] duration-(--dur)"
                :style="{ width: `${percent}%` }"
            />
        </div>
        <slot />
        <span class="min-w-14 text-right text-sm font-bold text-ink2">{{
            t('flashcards.counter', { n: Math.min(current + 1, total), total })
        }}</span>
    </div>
</template>
