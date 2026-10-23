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

// Modo corrida (J3-F3, PLA-04): 5 minutos, só vencidos.
const RUSH_SECONDS = 5 * 60;
const rush = computed(() => props.mode === 'rush');
const secondsLeft = ref(RUSH_SECONDS);
const timeUp = ref(false);
let timer: ReturnType<typeof setInterval> | undefined;

const clock = computed(() => {
    const minutes = Math.floor(secondsLeft.value / 60);
    const seconds = String(secondsLeft.value % 60).padStart(2, '0');

    return `${minutes}:${seconds}`;
});

const current = computed(() => queue.value[index.value]);
const done = computed(
    () =>
        queue.value.length > 0 &&
        (timeUp.value || index.value >= queue.value.length),
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

// Fim de sessão (J3-F4): resumo, XP e o próximo passo, sem voltar à home.
// O XP segue o protótipo (2 por resposta e 10 por sessão concluída) e é
// registrado de verdade na J5, a partir de card_reviews (GAM-03).
const XP_PER_ANSWER = 2;
const XP_PER_SESSION = 10;

const summary = computed(() =>
    [
        t('flashcards.again'),
        t('flashcards.hard'),
        t('flashcards.good'),
        t('flashcards.easy'),
    ].map((label, offset) => ({
        rating: offset + 1,
        label,
        count: answers.value.filter((answer) => answer.rating === offset + 1)
            .length,
    })),
);

const xp = computed(
    () => answers.value.length * XP_PER_ANSWER + XP_PER_SESSION,
);

const nextStep = computed(() =>
    props.lesson
        ? {
              label: t('flashcards.nextLesson'),
              href: `/aulas/${props.lesson.ulid}`,
          }
        : { label: t('flashcards.nextVocabulary'), href: '/vocabulario' },
);

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

function tick(): void {
    if (done.value) {
        clearInterval(timer);

        return;
    }

    secondsLeft.value -= 1;

    if (secondsLeft.value <= 0) {
        timeUp.value = true;
        clearInterval(timer);
    }
}

onMounted(() => {
    window.addEventListener('keydown', onKey);

    if (rush.value && queue.value.length > 0) {
        timer = setInterval(tick, 1000);
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey);
    clearInterval(timer);
});
</script>

<template>
    <Head :title="t('flashcards.title')" />

    <template v-if="queue.length === 0">
        <Card>
            <EmptyState
                icon="style"
                :title="
                    rush
                        ? t('flashcards.rushEmptyTitle')
                        : t('flashcards.emptyTitle')
                "
            >
                {{
                    rush
                        ? t('flashcards.rushEmptyBody')
                        : lesson
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
            <h1 class="m-0 type-title">
                {{
                    rush
                        ? t('flashcards.rushDoneTitle')
                        : t('flashcards.doneTitle')
                }}
            </h1>
            <p class="m-0 text-ink2">
                {{
                    rush
                        ? t('flashcards.rushDoneBody')
                        : t('flashcards.doneBody')
                }}
            </p>
            <ul
                class="m-0 grid w-full list-none grid-cols-4 gap-2 p-0"
                :aria-label="t('flashcards.summaryLabel')"
            >
                <li
                    v-for="stat in summary"
                    :key="stat.rating"
                    class="flex flex-col items-center rounded-m bg-sf2 px-2 py-3"
                >
                    <strong class="type-title">{{ stat.count }}</strong>
                    <span class="text-sm text-ink2">{{ stat.label }}</span>
                </li>
            </ul>
            <p
                class="m-0 inline-flex items-center gap-1.5 rounded-full bg-ambt px-3 py-1.5 font-extrabold text-ambd"
            >
                <Icon name="star" filled :size="20" />{{
                    t('flashcards.xp', { n: xp })
                }}
            </p>
            <div class="flex w-full flex-col gap-2">
                <Button block :href="nextStep.href" icon="arrow_forward">{{
                    nextStep.label
                }}</Button>
                <Button variant="secondary" block href="/hoje">{{
                    t('flashcards.backToday')
                }}</Button>
            </div>
        </Card>
    </template>

    <template v-else-if="current">
        <h1 class="sr-only">
            {{ rush ? t('flashcards.rushTitle') : t('flashcards.title') }}
        </h1>
        <SessionHeader :current="index" :total="queue.length" @exit="exit">
            <span
                v-if="rush"
                role="timer"
                :aria-label="t('flashcards.rushTime', { time: clock })"
                class="inline-flex items-center gap-1 rounded-full bg-ambt px-2.5 py-1 text-sm font-bold text-ambd tabular-nums"
            >
                <Icon name="timer" :size="17" />{{ clock }}
            </span>
        </SessionHeader>

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
