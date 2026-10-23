<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Banner from '@/components/fixa/Banner.vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import Flashcard from '@/components/fixa/Flashcard.vue';
import Icon from '@/components/fixa/Icon.vue';
import OriginTag from '@/components/fixa/OriginTag.vue';
import RateBar from '@/components/fixa/RateBar.vue';
import SessionHeader from '@/components/fixa/SessionHeader.vue';
import { t } from '@/i18n';
import { postJson } from '@/lib/http';
import { shortDate } from '@/lib/lessons';
import type { SessionCard } from '@/types/lessons';

/**
 * Sessão de flashcards em modo foco (J3-F1, protótipo tela 6). Vencidos
 * vêm antes dos novos (FLA-04); "Errei" devolve o cartão ao fim da sessão.
 */
const props = defineProps<{
    mode: 'normal' | 'rush';
    lesson: { ulid: string; title: string } | null;
    cards: SessionCard[];
}>();

type Answer = { card: string; rating: number };

const queue = ref<SessionCard[]>(props.cards.map((card) => ({ ...card })));
const index = ref(0);
const flipped = ref(false);
const answers = ref<Answer[]>([]);
const sendError = ref(false);
const shownAt = ref(Date.now());

const current = computed(() => queue.value[index.value]);
const done = computed(
    () => queue.value.length > 0 && index.value >= queue.value.length,
);

const remaining = computed(() => {
    const left = queue.value.slice(index.value + 1);
    const due = left.filter((card) => !card.is_new).length;
    const fresh = left.length - due;

    return t('flashcards.remaining', {
        due:
            due === 1
                ? t('flashcards.dueOne')
                : t('flashcards.dueMany', { n: due }),
        new:
            fresh === 1
                ? t('flashcards.newOne')
                : t('flashcards.newMany', { n: fresh }),
    });
});

type ReviewResponse = {
    card: string;
    interval_days: number;
    preview: number[];
};

async function rate(rating: number): Promise<void> {
    const card = current.value;

    if (!card || !flipped.value) {
        return;
    }

    const durationMs = Date.now() - shownAt.value;
    answers.value.push({ card: card.ulid, rating });

    // "Errei": o cartão volta no fim desta sessão, já como revisão.
    if (rating === 1) {
        queue.value.push({ ...card, is_new: false, preview: [0, 1, 2, 4] });
    }

    next();

    try {
        const saved = await postJson<ReviewResponse>(
            `/praticar/flashcards/${card.ulid}/respostas`,
            {
                rating,
                duration_ms: durationMs,
                client_review_id: crypto.randomUUID(),
            },
        );

        if (rating === 1) {
            const again = queue.value.findLast(
                (item) => item.ulid === saved.card,
            );

            if (again) {
                again.preview = saved.preview;
            }
        }

        sendError.value = false;
    } catch {
        sendError.value = true;
    }
}

function next(): void {
    index.value += 1;
    flipped.value = false;
    shownAt.value = Date.now();
}

function exit(): void {
    router.visit(props.lesson ? `/aulas/${props.lesson.ulid}` : '/hoje');
}

function onKey(event: KeyboardEvent): void {
    const target = event.target as HTMLElement | null;

    if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) {
        return;
    }

    if (flipped.value && ['1', '2', '3', '4'].includes(event.key)) {
        event.preventDefault();
        void rate(Number(event.key));
    }
}

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <Head :title="t('flashcards.title')" />

    <template v-if="queue.length === 0">
        <Card>
            <EmptyState icon="style" :title="t('flashcards.emptyTitle')">
                {{
                    lesson
                        ? t('flashcards.emptyLessonBody')
                        : t('flashcards.emptyBody')
                }}
                <template #action>
                    <Button variant="secondary" class="mt-2" @click="exit">{{
                        t('flashcards.backToday')
                    }}</Button>
                </template>
            </EmptyState>
        </Card>
    </template>

    <template v-else-if="done">
        <Card class="flex flex-col items-start gap-3">
            <Icon name="task_alt" :size="36" class="text-okd" />
            <h1 class="m-0 type-title">{{ t('flashcards.doneTitle') }}</h1>
            <p class="m-0 text-ink2">{{ t('flashcards.doneBody') }}</p>
            <Button @click="exit">{{ t('flashcards.backToday') }}</Button>
        </Card>
    </template>

    <template v-else-if="current">
        <h1 class="sr-only">{{ t('flashcards.title') }}</h1>
        <SessionHeader :current="index" :total="queue.length" @exit="exit" />

        <div class="flex flex-wrap items-center gap-2">
            <span
                class="inline-flex items-center gap-1 rounded-xs px-2.25 py-1 text-[12.5px] font-bold"
                :class="
                    current.is_new ? 'bg-infot text-infod' : 'bg-ambt text-ambd'
                "
            >
                <Icon
                    :name="current.is_new ? 'fiber_new' : 'schedule'"
                    :size="15"
                />{{
                    current.is_new
                        ? t('flashcards.isNew')
                        : t('flashcards.isDue')
                }}
            </span>
            <OriginTag
                :lesson-date="
                    current.lesson_date
                        ? shortDate(current.lesson_date)
                        : undefined
                "
            />
        </div>

        <Banner
            v-if="sendError"
            tone="warning"
            icon="cloud_off"
            :title="t('flashcards.sendError')"
        />

        <Flashcard
            :key="`${current.ulid}-${index}`"
            v-model:flipped="flipped"
            :front="current.front"
            :back="current.back"
            :example="current.example"
            :example-translation="current.example_translation"
            :part-of-speech="current.part_of_speech"
            @swipe="(direction) => rate(direction === 'right' ? 3 : 1)"
        />

        <Button v-if="!flipped" block icon="flip" @click="flipped = true">{{
            t('flashcards.showAnswer')
        }}</Button>
        <template v-else>
            <RateBar :preview="current.preview" @rate="rate" />
            <p
                class="m-0 flex items-center justify-center gap-1.5 text-[13px] text-ink2"
            >
                <Icon name="swipe" :size="18" />{{ t('flashcards.swipeHint') }}
            </p>
        </template>

        <p class="m-0 text-center text-sm text-ink2">{{ remaining }}</p>
        <p class="m-0 text-center text-[13px] text-ink2">
            {{ t('flashcards.keysHint') }}
        </p>
    </template>
</template>
