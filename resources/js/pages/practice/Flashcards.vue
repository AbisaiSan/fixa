<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import CardFormSheet from '@/components/cards/CardFormSheet.vue';
import type { CardFields } from '@/components/cards/CardFormSheet.vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import Flashcard from '@/components/fixa/Flashcard.vue';
import Icon from '@/components/fixa/Icon.vue';
import OriginTag from '@/components/fixa/OriginTag.vue';
import RateBar from '@/components/fixa/RateBar.vue';
import SessionHeader from '@/components/fixa/SessionHeader.vue';
import Sheet from '@/components/fixa/Sheet.vue';
import Toast from '@/components/fixa/Toast.vue';
import ReportSheet from '@/components/lessons/ReportSheet.vue';
import NextBlockActions from '@/components/plan/NextBlockActions.vue';
import { t } from '@/i18n';
import { ptBR } from '@/i18n/pt-BR';
import {
    enqueueReview,
    flushReviews,
    pendingReviews,
} from '@/lib/reviewOutbox';
import type { SentReview } from '@/lib/reviewOutbox';
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

// Fim de sessão (J3-F4, J5-F2): resumo, XP e o próximo bloco do plano.
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

    // Vai para a fila do aparelho e é enviada já, ou quando a conexão voltar (J3-F7).
    enqueueReview({
        card: card.ulid,
        rating,
        duration_ms: durationMs,
        client_review_id: crypto.randomUUID(),
    });
    await send();
}

const pending = ref(pendingReviews().length);

function onSent(saved: SentReview): void {
    // O cartão que voltou depois de "Errei" ganha a prévia certa.
    const again = queue.value
        .slice(index.value)
        .findLast((item) => item.ulid === saved.card);

    if (again && !again.is_new) {
        again.preview = saved.preview;
    }
}

async function send(): Promise<void> {
    await flushReviews(onSent);
    pending.value = pendingReviews().length;
}

// Fim da corrida (J5-B5, PLA-04): depois de enviar as respostas, o servidor
// confere a regra e conta o dia para a sequência.
watch(done, async (finished) => {
    if (!finished || !rush.value) {
        return;
    }

    await send();

    try {
        await postJson('/praticar/corrida', {});
    } catch {
        // Sem conexão a corrida não conta agora; as respostas seguem na fila.
    }
});

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

    if (
        anySheetOpen.value ||
        (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))
    ) {
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

// Opções do cartão (J3-F6, FLA-06): editar, criar, suspender e reportar.
const menuOpen = ref(false);
const formOpen = ref(false);
const editing = ref(false);
const reportOpen = ref(false);
const reported = ref<string[]>([]);
const toast = ref<string | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | undefined;
let removeListener: VoidFunction | undefined;

const anySheetOpen = computed(
    () => menuOpen.value || formOpen.value || reportOpen.value,
);

function showToast(code: string | null | undefined): void {
    const messages: Record<string, string> = ptBR.cards.flash;

    if (!code || !messages[code]) {
        return;
    }

    toast.value = messages[code];
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (toast.value = null), 4000);
}

function openForm(edit: boolean): void {
    editing.value = edit;
    menuOpen.value = false;
    formOpen.value = true;
}

function onSaved(fields: CardFields): void {
    const ulid = current.value?.ulid;

    if (!editing.value || !ulid) {
        return;
    }

    queue.value
        .filter((item) => item.ulid === ulid)
        .forEach((item) => Object.assign(item, fields));
}

function suspend(): void {
    const ulid = current.value?.ulid;
    menuOpen.value = false;

    if (!ulid) {
        return;
    }

    router.post(
        `/cartoes/${ulid}/suspender`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['flash'],
            onSuccess: () => skip(ulid),
        },
    );
}

function openReport(): void {
    menuOpen.value = false;
    reportOpen.value = true;
}

// Tira o cartão do resto desta sessão e segue para o próximo.
function skip(ulid: string): void {
    queue.value = [
        ...queue.value.slice(0, index.value + 1),
        ...queue.value
            .slice(index.value + 1)
            .filter((item) => item.ulid !== ulid),
    ];
    next();
}

onMounted(() => {
    removeListener = router.on('success', (event) =>
        showToast(event.detail.page.props.flash as string | null),
    );
    window.addEventListener('keydown', onKey);
    window.addEventListener('online', send);
    // Respostas de uma sessão anterior que ficaram sem conexão.
    void send();

    if (rush.value && queue.value.length > 0) {
        timer = setInterval(tick, 1000);
    }
});

onBeforeUnmount(() => {
    removeListener?.();
    clearTimeout(toastTimer);
    window.removeEventListener('keydown', onKey);
    window.removeEventListener('online', send);
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
            <NextBlockActions
                :current="lesson ? 'vocabulary' : 'flashcards'"
                :gained="xp"
            />
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
            <button
                type="button"
                class="ml-auto grid size-11 place-items-center rounded-s text-ink2 hover:bg-sf2"
                :aria-label="t('cards.menuTitle')"
                @click="menuOpen = true"
            >
                <Icon name="more_horiz" />
            </button>
        </div>

        <div
            v-if="reported.includes(current.ulid)"
            role="status"
            class="flex flex-wrap items-center gap-3 rounded-m bg-infot px-3.5 py-3"
        >
            <Icon name="flag" class="text-infod" />
            <span class="min-w-0 flex-1 text-sm"
                ><strong>{{ t('cards.reportedTitle') }}</strong>
                {{ t('cards.reportedBody') }}</span
            >
            <Button variant="ghost" @click="skip(current.ulid)">{{
                t('cards.skip')
            }}</Button>
        </div>

        <p
            v-if="pending > 0"
            role="status"
            class="m-0 flex items-center gap-1.5 text-sm text-ink2"
        >
            <Icon name="cloud_upload" :size="18" />{{
                pending === 1
                    ? t('flashcards.pendingOne')
                    : t('flashcards.pending', { n: pending })
            }}
        </p>

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

    <Sheet v-model:open="menuOpen" :title="t('cards.menuTitle')">
        <div class="flex flex-col gap-1">
            <Button
                variant="ghost"
                icon="edit"
                class="justify-start"
                @click="openForm(true)"
                >{{ t('cards.edit') }}</Button
            >
            <Button
                variant="ghost"
                icon="add_card"
                class="justify-start"
                @click="openForm(false)"
                >{{ t('cards.create') }}</Button
            >
            <Button
                variant="ghost"
                icon="pause_circle"
                class="justify-start"
                @click="suspend"
            >
                <span class="flex flex-col items-start">
                    <span>{{ t('cards.suspend') }}</span>
                    <span class="text-[12.5px] font-semibold text-ink2">{{
                        t('cards.suspendHint')
                    }}</span>
                </span>
            </Button>
            <Button
                variant="ghost"
                icon="flag"
                class="justify-start"
                @click="openReport"
                >{{ t('cards.report') }}</Button
            >
        </div>
    </Sheet>

    <CardFormSheet
        v-model:open="formOpen"
        :card="editing && current ? current : null"
        :lesson="lesson?.ulid ?? null"
        @saved="onSaved"
    />

    <ReportSheet
        v-if="current"
        v-model:open="reportOpen"
        :url="`/cartoes/${current.ulid}/reportes`"
        partial
        @sent="reported.push(current.ulid)"
    />

    <div
        v-if="toast"
        class="pointer-events-none fixed inset-x-0 bottom-8 z-30 flex justify-center px-4"
    >
        <Toast tone="info">{{ toast }}</Toast>
    </div>
</template>
