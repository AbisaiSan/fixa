<script setup lang="ts">
import { computed } from 'vue';
import { t } from '@/i18n';
import { diffWords } from '@/lib/wordDiff';
import Icon from './Icon.vue';
import TargetText from './TargetText.vue';

/**
 * Correção de um item (J4-F3, ATV-02, ATV-03): resultado com ícone e rótulo,
 * XP, a resposta com o erro destacado, a versão corrigida, o porquê e a
 * alternativa mais natural. Tudo como texto, nunca HTML (IAS-02), e
 * anunciado por aria-live.
 */
const props = defineProps<{
    verdict: 'total' | 'partial' | 'wrong';
    xp: number;
    answer: string;
    corrected: string;
    natural: string;
    explanation: string;
    choice?: boolean;
}>();

defineEmits<{ report: [] }>();

const result = computed(
    () =>
        ({
            total: {
                icon: 'check_circle',
                label: props.choice
                    ? t('feedback.totalChoice')
                    : t('feedback.total'),
                classes: 'bg-okt text-okd',
            },
            partial: {
                icon: 'contrast',
                label: t('feedback.partial'),
                classes: 'bg-ambt text-ambd',
            },
            wrong: {
                icon: 'cancel',
                label: t('feedback.wrong'),
                classes: 'bg-errt text-errd',
            },
        })[props.verdict],
);

const diff = computed(() => diffWords(props.answer, props.corrected));
const showCorrection = computed(() => props.verdict !== 'total');
</script>

<template>
    <section
        aria-live="polite"
        class="flex flex-col gap-4 rounded-l border border-line bg-sf p-5"
    >
        <div class="flex flex-wrap items-center gap-2">
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 font-extrabold"
                :class="result.classes"
            >
                <Icon :name="result.icon" filled :size="20" />{{ result.label }}
            </span>
            <span
                class="ml-auto inline-flex items-center gap-1 font-extrabold text-ambd"
            >
                <Icon name="star" filled :size="18" />{{
                    t('feedback.xp', { n: xp })
                }}
            </span>
        </div>

        <template v-if="!choice">
            <div>
                <p class="m-0 mb-1 type-label text-ink2">
                    {{ t('feedback.yours') }}
                </p>
                <TargetText as="p" class="m-0">
                    <template
                        v-for="(token, index) in diff.answer"
                        :key="index"
                    >
                        <mark
                            v-if="token.changed && showCorrection"
                            class="rounded-xs bg-errt px-0.5 text-ink"
                            >{{ token.text }}</mark
                        ><span v-else>{{ token.text }}</span
                        >{{ ' ' }}
                    </template>
                </TargetText>
            </div>
            <div v-if="showCorrection">
                <p class="m-0 mb-1 type-label text-ink2">
                    {{ t('feedback.corrected') }}
                </p>
                <TargetText as="p" class="m-0">
                    <template
                        v-for="(token, index) in diff.corrected"
                        :key="index"
                    >
                        <mark
                            v-if="token.changed"
                            class="rounded-xs bg-okt px-0.5 text-ink"
                            >{{ token.text }}</mark
                        ><span v-else>{{ token.text }}</span
                        >{{ ' ' }}
                    </template>
                </TargetText>
            </div>
        </template>

        <div v-if="explanation">
            <p class="m-0 mb-1 type-label text-ink2">{{ t('feedback.why') }}</p>
            <p class="m-0 type-body">{{ explanation }}</p>
        </div>

        <div v-if="!choice && natural">
            <p class="m-0 mb-1 type-label text-ink2">
                {{ t('feedback.natural') }}
            </p>
            <TargetText as="p" class="m-0">{{ natural }}</TargetText>
        </div>

        <p
            v-if="showCorrection"
            class="m-0 flex items-center gap-1.5 text-sm text-ink2"
        >
            <Icon name="auto_stories" :size="18" />{{ t('feedback.notebook') }}
        </p>

        <button
            type="button"
            class="inline-flex items-center gap-1.5 self-start rounded-s px-2 py-1.5 text-sm font-bold text-ink2 hover:bg-sf2"
            @click="$emit('report')"
        >
            <Icon name="flag" :size="18" />{{ t('feedback.report') }}
        </button>
    </section>
</template>
