<script setup lang="ts">
import { computed } from 'vue';
import { t } from '@/i18n';
import { intervalLabel } from '@/lib/intervals';

/**
 * As 4 notas depois do verso (FLA-03), com o intervalo previsto de cada uma
 * e as teclas 1 a 4. A cor nunca é a única pista: cada botão tem o nome.
 */
const props = defineProps<{ preview: number[]; disabled?: boolean }>();

const emit = defineEmits<{ rate: [rating: number] }>();

const buttons = computed(() => [
    {
        rating: 1,
        label: t('flashcards.again'),
        classes: 'bg-errt text-ink',
    },
    { rating: 2, label: t('flashcards.hard'), classes: 'bg-ambt text-ink' },
    {
        rating: 3,
        label: t('flashcards.good'),
        classes: 'bg-prid text-onpri',
    },
    { rating: 4, label: t('flashcards.easy'), classes: 'bg-okt text-ink' },
]);
</script>

<template>
    <div
        role="group"
        :aria-label="t('flashcards.rateLabel')"
        class="grid grid-cols-4 gap-2"
    >
        <button
            v-for="button in buttons"
            :key="button.rating"
            type="button"
            :disabled="disabled"
            :aria-keyshortcuts="String(button.rating)"
            class="flex min-h-15.5 flex-col items-center justify-center gap-0.5 rounded-m px-1 py-1.5 transition duration-(--dur-fast) hover:brightness-97 active:scale-98 disabled:opacity-45"
            :class="button.classes"
            @click="emit('rate', button.rating)"
        >
            <span class="font-extrabold">{{ button.label }}</span>
            <span class="text-[12.5px] font-semibold">{{
                intervalLabel(props.preview[button.rating - 1] ?? 0)
            }}</span>
        </button>
    </div>
</template>
