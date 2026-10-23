<script setup lang="ts">
import { computed } from 'vue';
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
            class="flex min-h-80 cursor-pointer flex-col items-center justify-center gap-3 rounded-xl border border-line bg-sf p-6 text-center shadow-sh2 select-none focus-visible:outline-3 focus-visible:outline-offset-3 focus-visible:outline-focus"
            @click="flip"
            @keydown="onKey"
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
        <p class="sr-only" aria-live="polite">{{ announcement }}</p>
    </div>
</template>
