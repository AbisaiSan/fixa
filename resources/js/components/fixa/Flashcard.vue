<script setup lang="ts">
import { computed, ref } from 'vue';
import { t } from '@/i18n';
import Icon from './Icon.vue';
import TargetText from './TargetText.vue';

/**
 * Cartão que gira com clique, Espaço ou Enter (J3-F1). O leitor de tela
 * ouve a frente e, ao virar, o verso, por uma região aria-live.
 */
const props = defineProps<{
    front: string;
    back: string;
    example?: string | null;
    exampleTranslation?: string | null;
    partOfSpeech?: string | null;
}>();

const flipped = defineModel<boolean>('flipped', { default: false });

const emit = defineEmits<{ swipe: [direction: 'left' | 'right'] }>();

// Gesto opcional no verso (J3-F2): direita é Bom, esquerda é Errei. Os
// botões continuam sempre visíveis, o gesto é só um atalho.
const SWIPE_DISTANCE = 100;
const HINT_DISTANCE = 40;
const dragX = ref(0);
let startX: number | null = null;
let moved = false;

function onPointerDown(event: PointerEvent): void {
    if (!flipped.value) {
        return;
    }

    startX = event.clientX;
    moved = false;
    (event.currentTarget as HTMLElement).setPointerCapture?.(event.pointerId);
}

function onPointerMove(event: PointerEvent): void {
    if (startX === null) {
        return;
    }

    dragX.value = event.clientX - startX;
    moved ||= Math.abs(dragX.value) > 6;
}

function onPointerUp(): void {
    if (startX === null) {
        return;
    }

    startX = null;

    if (dragX.value > SWIPE_DISTANCE) {
        emit('swipe', 'right');
    } else if (dragX.value < -SWIPE_DISTANCE) {
        emit('swipe', 'left');
    }

    dragX.value = 0;
}

function onClick(): void {
    if (moved) {
        moved = false;

        return;
    }

    flip();
}

const label = computed(() =>
    flipped.value
        ? t('flashcards.backLabel', { text: props.back })
        : t('flashcards.frontLabel', { text: props.front }),
);

const announcement = computed(() =>
    flipped.value
        ? [props.back, props.example, props.exampleTranslation]
              .filter(Boolean)
              .join('. ')
        : '',
);

function flip(): void {
    flipped.value = !flipped.value;
}

function onKey(event: KeyboardEvent): void {
    if (event.key === ' ' || event.key === 'Enter') {
        event.preventDefault();
        flip();
    }
}
</script>

<template>
    <div>
        <div
            role="button"
            tabindex="0"
            :aria-label="label"
            :aria-pressed="flipped"
            class="flex min-h-80 cursor-pointer touch-pan-y flex-col items-center justify-center gap-3 rounded-xl border border-line bg-sf p-6 text-center shadow-sh2 select-none focus-visible:outline-3 focus-visible:outline-offset-3 focus-visible:outline-focus"
            :style="
                dragX === 0
                    ? undefined
                    : {
                          transform: `translateX(${dragX}px) rotate(${(dragX * 0.04).toFixed(2)}deg)`,
                      }
            "
            @click="onClick"
            @keydown="onKey"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointercancel="onPointerUp"
        >
            <div
                :key="String(flipped)"
                class="flex flex-col items-center gap-3 motion-safe:animate-[fx-flip_var(--dur)_var(--ease-out)]"
            >
                <template v-if="!flipped">
                    <span
                        v-if="partOfSpeech"
                        class="text-[12.5px] font-bold tracking-wide text-ink2 uppercase"
                        >{{ partOfSpeech }}</span
                    >
                    <TargetText size="large" class="type-target">{{
                        front
                    }}</TargetText>
                    <span
                        class="mt-2 inline-flex items-center gap-1 text-sm text-ink2"
                        aria-hidden="true"
                    >
                        <Icon name="touch_app" :size="18" />{{
                            t('flashcards.tapToFlip')
                        }}
                    </span>
                </template>
                <template v-else>
                    <TargetText class="text-ink2">{{ front }}</TargetText>
                    <span class="type-title">{{ back }}</span>
                    <span
                        v-if="example"
                        class="my-1 h-px w-16 bg-line"
                        aria-hidden="true"
                    />
                    <TargetText v-if="example">{{ example }}</TargetText>
                    <span v-if="exampleTranslation" class="text-sm text-ink2">{{
                        exampleTranslation
                    }}</span>
                </template>
            </div>
        </div>
        <div
            v-if="Math.abs(dragX) > HINT_DISTANCE"
            class="mt-2 text-center font-extrabold"
            :class="dragX > 0 ? 'text-prid' : 'text-errd'"
            aria-hidden="true"
        >
            {{
                dragX > 0
                    ? `${t('flashcards.good')} →`
                    : `← ${t('flashcards.again')}`
            }}
        </div>
        <p class="sr-only" aria-live="polite">{{ announcement }}</p>
    </div>
</template>
