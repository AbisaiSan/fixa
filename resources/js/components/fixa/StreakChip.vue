<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { t } from '@/i18n';
import Icon from './Icon.vue';

/**
 * Sequência de dias (J5-F1, GAM-01). Sem sequência, o texto convida a
 * recomeçar, sem culpa (GAM-05). Leva ao progresso.
 */
const props = withDefaults(defineProps<{ days: number; href?: string }>(), {
    href: '/progresso',
});

const label = computed(() =>
    props.days === 0
        ? t('streak.restart')
        : props.days === 1
          ? t('streak.one')
          : t('streak.many'),
);

const aria = computed(() =>
    props.days === 0
        ? t('streak.ariaRestart')
        : t('streak.aria', { n: props.days }),
);
</script>

<template>
    <Link
        :href="href"
        :aria-label="aria"
        class="flex min-h-11 flex-col items-start gap-0.5 rounded-l border border-line bg-sf px-4 py-3 text-ink no-underline shadow-sh1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-prid"
    >
        <span class="flex items-center gap-1.5">
            <Icon
                name="local_fire_department"
                :filled="days > 0"
                :size="22"
                class="text-ambd"
            />
            <span class="font-display text-2xl leading-none">{{ days }}</span>
        </span>
        <span class="text-sm text-ink2">{{ label }}</span>
    </Link>
</template>
