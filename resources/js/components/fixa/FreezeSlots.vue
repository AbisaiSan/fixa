<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { t } from '@/i18n';
import Icon from './Icon.vue';

/**
 * Congelamentos disponíveis (J5-F1, GAM-02): até 2, um por semana. Cada
 * vaga cheia ou vazia tem forma diferente, e o texto diz o saldo.
 */
const props = withDefaults(
    defineProps<{ count: number; max?: number; href?: string }>(),
    { max: 2, href: '/progresso' },
);

const slots = computed(() =>
    Array.from({ length: props.max }, (_, index) => index < props.count),
);

const label = computed(() =>
    props.count === 0
        ? t('freezes.none')
        : props.count === 1
          ? t('freezes.one')
          : t('freezes.many'),
);
</script>

<template>
    <Link
        :href="href"
        :aria-label="t('freezes.aria', { n: count, max })"
        class="flex min-h-11 flex-col items-start gap-0.5 rounded-l border border-line bg-sf px-4 py-3 text-ink no-underline shadow-sh1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-prid"
    >
        <span class="flex items-center gap-1.5">
            <span class="flex gap-0.5 text-infod">
                <Icon
                    v-for="(on, index) in slots"
                    :key="index"
                    :name="on ? 'ac_unit' : 'radio_button_unchecked'"
                    :size="20"
                />
            </span>
            <span class="font-display text-2xl leading-none">{{ count }}</span>
            <span class="text-sm text-ink2">{{
                t('freezes.of', { max })
            }}</span>
        </span>
        <span class="text-sm text-ink2">{{ label }}</span>
    </Link>
</template>
