<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { t } from '@/i18n';
import Icon from './Icon.vue';

/**
 * Meta cumprida (J5-F3): confete, sequência +1 e o XP subindo. Com
 * "reduzir movimento" no sistema, mostra só o texto e o número final.
 */
const props = withDefaults(
    defineProps<{
        streak: number;
        xpFrom: number;
        xpTo: number;
        reduceMotion?: boolean;
    }>(),
    { reduceMotion: undefined },
);

const still = computed(
    () =>
        props.reduceMotion ??
        (typeof window !== 'undefined' &&
            window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ===
                true),
);

const xpShown = ref(still.value ? props.xpTo : props.xpFrom);
let frame = 0;

onMounted(() => {
    if (still.value || props.xpTo <= props.xpFrom) {
        xpShown.value = props.xpTo;

        return;
    }

    const started = performance.now();
    const step = (now: number): void => {
        const progress = Math.min(1, (now - started) / 900);
        xpShown.value = Math.round(
            props.xpFrom + (props.xpTo - props.xpFrom) * progress,
        );

        if (progress < 1) {
            frame = requestAnimationFrame(step);
        }
    };
    frame = requestAnimationFrame(step);
});

onBeforeUnmount(() => cancelAnimationFrame(frame));

// Cores dos tokens, nunca fixas; posições e atrasos variados.
const pieces = Array.from({ length: 18 }, (_, index) => ({
    left: `${(index * 53) % 100}%`,
    delay: `${(index % 6) * 70}ms`,
    color: ['bg-pri', 'bg-ambd', 'bg-okd', 'bg-infod'][index % 4],
}));
</script>

<template>
    <div
        role="status"
        class="relative flex w-full flex-col items-center gap-2 overflow-hidden rounded-l bg-okt px-4 py-5 text-center text-okd"
    >
        <div
            v-if="!still"
            class="pointer-events-none absolute inset-0"
            aria-hidden="true"
        >
            <span
                v-for="(piece, index) in pieces"
                :key="index"
                class="absolute top-0 block h-2.5 w-1.5 rounded-xs"
                :class="piece.color"
                :style="{
                    left: piece.left,
                    animation: `fx-confetti 1.4s ease-out ${piece.delay} both`,
                }"
            />
        </div>
        <Icon name="emoji_events" filled :size="36" />
        <p class="m-0 type-title">{{ t('celebration.title') }}</p>
        <p class="m-0 flex items-center gap-1.5 font-bold">
            <Icon name="local_fire_department" filled :size="20" />{{
                streak === 1
                    ? t('celebration.streakOne')
                    : t('celebration.streak', { n: streak })
            }}
        </p>
        <p
            class="m-0 inline-flex items-center gap-1.5 rounded-full bg-sf px-3 py-1 font-extrabold text-ambd tabular-nums"
        >
            <Icon name="star" filled :size="18" />{{
                t('celebration.xp', { n: xpShown })
            }}
        </p>
    </div>
</template>
