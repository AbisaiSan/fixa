<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, useTemplateRef, watch } from 'vue';
import Banner from '@/components/fixa/Banner.vue';
import Button from '@/components/fixa/Button.vue';
import NextBlockActions from '@/components/plan/NextBlockActions.vue';
import Card from '@/components/fixa/Card.vue';
import FeedbackPanel from '@/components/fixa/FeedbackPanel.vue';
import Icon from '@/components/fixa/Icon.vue';
import TargetText from '@/components/fixa/TargetText.vue';
import AiLimitReached from '@/components/practice/AiLimitReached.vue';
import ReportSheet from '@/components/lessons/ReportSheet.vue';
import { useTargetLang } from '@/composables/useTargetLang';
import { t } from '@/i18n';
import type { StringKey } from '@/i18n';
import { HttpError, postJson } from '@/lib/http';
import { shortDate } from '@/lib/lessons';
import type { PracticeItem, PracticeAttempt } from '@/types/practice';

/**
 * Sessão de escrita e de ponto fraco em modo foco (J4-F2, J4-F7, protótipo
 * tela 8). Uma pergunta por vez, correção explicada e XP, tudo pelo teclado.
 */
const props = defineProps<{
    session: {
        ulid: string;
        kind: 'writing' | 'weak_point';
        lesson: { ulid: string; title: string; lesson_date: string } | null;
    };
    items: PracticeItem[];
}>();

const targetLang = useTargetLang();
const weak = computed(() => props.session.kind === 'weak_point');

// Retoma de onde o aluno parou: os itens já respondidos ficam com a correção.
const attempts = ref<Record<string, PracticeAttempt>>(
    Object.fromEntries(
        props.items
            .filter((item) => item.attempt)
            .map((item) => [item.ulid, item.attempt as PracticeAttempt]),
    ),
);
const firstOpen = props.items.findIndex((item) => !item.attempt);
const index = ref(firstOpen === -1 ? props.items.length : firstOpen);
const answer = ref('');
const sending = ref(false);
const problem = ref<'retry' | 'offline' | null>(null);
const limitReached = ref(false);
const reportOpen = ref(false);
const reported = ref<string[]>([]);

const inputEl = useTemplateRef<HTMLInputElement | HTMLTextAreaElement>(
    'inputEl',
);
const nextButton = useTemplateRef<InstanceType<typeof Button>>('nextButton');

const current = computed(() => props.items[index.value]);
const done = computed(() => index.value >= props.items.length);
const feedback = computed(() =>
    current.value ? attempts.value[current.value.ulid] : undefined,
);
const xp = computed(() =>
    Object.values(attempts.value).reduce((sum, attempt) => sum + attempt.xp, 0),
);
const answeredCount = computed(() => Object.keys(attempts.value).length);

const typeLabels: Record<PracticeItem['type'], StringKey> = {
    fill: 'writing.typeFill',
    translate: 'writing.typeTranslate',
    context: 'writing.typeContext',
    choice: 'writing.typeChoice',
};

const contexts: Record<string, { label: StringKey; icon: string }> = {
    daily: { label: 'writing.contextDaily', icon: 'home' },
    work: { label: 'writing.contextWork', icon: 'work' },
    travel: { label: 'writing.contextTravel', icon: 'flight' },
};

// No "completar", a frase mostra a resposta no lugar da lacuna.
const sentenceParts = computed(() => {
    const [before = '', after = ''] = (current.value?.sentence ?? '').split(
        '___',
    );

    return { before, after };
});

const fullAnswer = computed(() => {
    const item = current.value;
    const given = feedback.value?.answer ?? answer.value;

    if (item?.type !== 'fill') {
        return given;
    }

    return `${sentenceParts.value.before}${given}${sentenceParts.value.after}`
        .replace(/\s*\([^)]*\)/g, '')
        .trim();
});

watch(
    index,
    async () => {
        answer.value = '';
        problem.value = null;
        await nextTick();
        inputEl.value?.focus();
    },
    { immediate: false },
);

onMounted(() => inputEl.value?.focus());

async function check(): Promise<void> {
    const item = current.value;

    if (!item || sending.value || answer.value.trim() === '') {
        return;
    }

    sending.value = true;
    problem.value = null;

    try {
        attempts.value[item.ulid] = await postJson<PracticeAttempt>(
            `/praticar/itens/${item.ulid}/respostas`,
            { answer: answer.value },
        );
        attempts.value[item.ulid].answer = answer.value.trim();
        await nextTick();
        (nextButton.value?.$el as HTMLElement | undefined)?.focus();
    } catch (error) {
        if (error instanceof HttpError && error.status === 429) {
            limitReached.value = true;
        } else {
            problem.value = error instanceof HttpError ? 'retry' : 'offline';
        }
    } finally {
        sending.value = false;
    }
}

function next(): void {
    index.value += 1;
}

function onAnswerKey(event: KeyboardEvent): void {
    // Enter corrige no campo de uma linha; no texto longo, Ctrl ou Cmd + Enter.
    if (
        event.key === 'Enter' &&
        (current.value?.type === 'fill' || event.ctrlKey || event.metaKey)
    ) {
        event.preventDefault();
        void check();
    }
}

function exit(): void {
    router.visit('/praticar');
}

const summary = computed(() => {
    const values = Object.values(attempts.value);

    return [
        {
            label: t('writing.statTotal'),
            n: values.filter((a) => a.verdict === 'total').length,
        },
        {
            label: t('writing.statPartial'),
            n: values.filter((a) => a.verdict === 'partial').length,
        },
        {
            label: t('writing.statWrong'),
            n: values.filter((a) => a.verdict === 'wrong').length,
        },
    ];
});
</script>

<template>
    <Head :title="weak ? t('writing.weakTitle') : t('writing.title')" />

    <AiLimitReached v-if="limitReached" @close="limitReached = false" />

    <template v-else-if="done">
        <Card class="flex flex-col items-start gap-4">
            <Icon name="task_alt" :size="36" class="text-okd" />
            <h1 class="m-0 type-title">
                {{ weak ? t('writing.doneWeakTitle') : t('writing.doneTitle') }}
            </h1>
            <p class="m-0 text-ink2">
                {{ weak ? t('writing.doneWeakBody') : t('writing.doneBody') }}
            </p>
            <ul
                class="m-0 grid w-full list-none grid-cols-3 gap-2 p-0"
                :aria-label="t('writing.summaryLabel')"
            >
                <li
                    v-for="stat in summary"
                    :key="stat.label"
                    class="flex flex-col items-center rounded-m bg-sf2 px-2 py-3"
                >
                    <strong class="type-title">{{ stat.n }}</strong>
                    <span class="text-sm text-ink2">{{ stat.label }}</span>
                </li>
            </ul>
            <p
                class="m-0 inline-flex items-center gap-1.5 rounded-full bg-ambt px-3 py-1.5 font-extrabold text-ambd"
            >
                <Icon name="star" filled :size="20" />{{
                    t('feedback.xp', { n: xp })
                }}
            </p>
            <NextBlockActions
                :current="weak ? 'weak_point' : 'writing'"
                :gained="xp"
            />
        </Card>
    </template>

    <template v-else-if="current">
        <h1 class="sr-only">
            {{ weak ? t('writing.weakTitle') : t('writing.title') }}
        </h1>

        <div class="flex items-center gap-3">
            <button
                type="button"
                class="grid size-11 shrink-0 place-items-center rounded-s text-ink2 hover:bg-sf2"
                :aria-label="t('writing.exit')"
                @click="exit"
            >
                <Icon name="close" />
            </button>
            <div
                role="progressbar"
                :aria-label="t('writing.progress')"
                aria-valuemin="0"
                :aria-valuemax="items.length"
                :aria-valuenow="answeredCount"
                class="h-2.5 flex-1 overflow-hidden rounded-full bg-sf2"
            >
                <div
                    class="h-full rounded-full bg-pri transition-[width] duration-(--dur)"
                    :style="{
                        width: `${Math.round((answeredCount / items.length) * 100)}%`,
                    }"
                />
            </div>
            <span
                class="inline-flex items-center gap-1 font-extrabold text-ambd"
                :aria-label="t('writing.xp', { n: xp })"
            >
                <Icon name="star" filled :size="18" />+{{ xp }}
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-[12.5px] font-bold">
            <span class="text-ink2">{{
                t('writing.counter', { n: index + 1, total: items.length })
            }}</span>
            <span class="rounded-xs bg-prit px-2.25 py-1 text-prid">{{
                t(typeLabels[current.type])
            }}</span>
            <span
                v-if="current.context && contexts[current.context]"
                class="inline-flex items-center gap-1 rounded-xs bg-sf2 px-2.25 py-1 text-ink2"
            >
                <Icon :name="contexts[current.context].icon" :size="15" />{{
                    t(contexts[current.context].label)
                }}
            </span>
            <span class="rounded-xs bg-sf2 px-2.25 py-1 text-ink2">{{
                session.lesson
                    ? t('writing.fromLesson', {
                          date: shortDate(session.lesson.lesson_date),
                      })
                    : t('writing.fromNotebook')
            }}</span>
        </div>

        <h2 class="m-0 type-title">
            {{ current.instruction ?? t(typeLabels[current.type]) }}
        </h2>

        <TargetText
            v-if="current.type === 'fill'"
            as="p"
            size="large"
            class="m-0"
            >{{ sentenceParts.before
            }}<span
                class="rounded-xs border-b-2 border-prid px-1 font-bold text-prid"
                >{{ (feedback?.answer ?? answer).trim() || '   ' }}</span
            >{{ sentenceParts.after }}</TargetText
        >
        <p
            v-else-if="current.type === 'translate'"
            class="m-0 rounded-m bg-sf2 px-4 py-3 font-bold type-body"
        >
            {{ current.sentence }}
        </p>
        <p
            v-else-if="current.type === 'context'"
            class="m-0 rounded-m bg-sf2 px-4 py-3 type-body"
        >
            {{ current.sentence }}
        </p>
        <p v-else class="m-0 text-ink2">{{ current.sentence }}</p>

        <div
            v-if="current.type === 'choice'"
            role="radiogroup"
            :aria-label="t('writing.options')"
            class="flex flex-col gap-2"
        >
            <label
                v-for="option in current.options ?? []"
                :key="option"
                class="flex min-h-13 cursor-pointer items-center gap-3 rounded-m border-[1.5px] px-4 py-2"
                :class="
                    feedback
                        ? option === feedback.correction.corrected
                            ? 'border-okd bg-okt'
                            : option === feedback.answer
                              ? 'border-errd bg-errt'
                              : 'border-line opacity-70'
                        : answer === option
                          ? 'border-prid bg-prit'
                          : 'border-line bg-sf'
                "
            >
                <input
                    v-model="answer"
                    type="radio"
                    name="choice"
                    :value="option"
                    :disabled="!!feedback"
                    class="m-0 size-5 accent-prid"
                />
                <TargetText>{{ option }}</TargetText>
                <span
                    v-if="feedback && option === feedback.correction.corrected"
                    class="ml-auto text-sm font-bold text-okd"
                    >{{ t('feedback.correct') }}</span
                >
                <span
                    v-else-if="feedback && option === feedback.answer"
                    class="ml-auto text-sm font-bold text-errd"
                    >{{ t('feedback.yourChoice') }}</span
                >
            </label>
        </div>

        <div v-else-if="!feedback">
            <label
                for="writing-answer"
                class="mb-1.5 block text-sm font-bold"
                >{{
                    t('writing.answerLabel', { language: targetLang.name })
                }}</label
            >
            <input
                v-if="current.type === 'fill'"
                id="writing-answer"
                ref="inputEl"
                v-model="answer"
                :lang="targetLang.code"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false"
                maxlength="500"
                :placeholder="t('writing.fillPlaceholder')"
                class="min-h-12.5 w-full rounded-s border-[1.5px] border-line bg-sf px-3.5 text-base text-ink placeholder:text-ink3 focus:border-prid focus:shadow-[0_0_0_3px_var(--prit)] focus:outline-none"
                @keydown="onAnswerKey"
            />
            <textarea
                v-else
                id="writing-answer"
                ref="inputEl"
                v-model="answer"
                :lang="targetLang.code"
                spellcheck="false"
                rows="3"
                maxlength="500"
                :placeholder="
                    t('writing.longPlaceholder', { language: targetLang.name })
                "
                class="w-full resize-y rounded-s border-[1.5px] border-line bg-sf px-3.5 py-3 text-base text-ink placeholder:text-ink3 focus:border-prid focus:shadow-[0_0_0_3px_var(--prit)] focus:outline-none"
                @keydown="onAnswerKey"
            />
        </div>

        <Banner
            v-if="problem"
            tone="warning"
            icon="cloud_off"
            :title="
                problem === 'offline'
                    ? t('writing.offline')
                    : t('writing.retryLater')
            "
        />

        <FeedbackPanel
            v-if="feedback"
            :verdict="feedback.verdict"
            :xp="feedback.xp"
            :answer="fullAnswer"
            :corrected="feedback.correction.corrected"
            :natural="feedback.correction.natural"
            :explanation="feedback.correction.explanation"
            :choice="current.type === 'choice'"
            @report="reportOpen = true"
        />
        <p
            v-if="feedback && reported.includes(current.ulid)"
            role="status"
            class="m-0 text-sm text-ink2"
        >
            {{ t('feedback.reported') }}
        </p>

        <Button
            v-if="!feedback"
            block
            :loading="sending"
            :disabled="answer.trim() === ''"
            @click="check"
            >{{
                current.type === 'choice'
                    ? t('writing.checkChoice')
                    : t('writing.check')
            }}</Button
        >
        <Button
            v-else
            ref="nextButton"
            block
            icon="arrow_forward"
            @click="next"
            >{{ t('writing.next') }}</Button
        >

        <ReportSheet
            v-model:open="reportOpen"
            :url="`/praticar/itens/${current.ulid}/reportes`"
            partial
            @sent="reported.push(current.ulid)"
        />
    </template>
</template>
