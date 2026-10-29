<script setup lang="ts">
import { Head, router, useForm, usePoll } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Banner from '@/components/fixa/Banner.vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import Checkbox from '@/components/fixa/Checkbox.vue';
import Icon from '@/components/fixa/Icon.vue';
import OriginTag from '@/components/fixa/OriginTag.vue';
import Sheet from '@/components/fixa/Sheet.vue';
import TargetText from '@/components/fixa/TargetText.vue';
import TextArea from '@/components/fixa/TextArea.vue';
import TextField from '@/components/fixa/TextField.vue';
import Toast from '@/components/fixa/Toast.vue';
import GenerationProgress from '@/components/lessons/GenerationProgress.vue';
import LessonFailure from '@/components/lessons/LessonFailure.vue';
import ReportedNotice from '@/components/lessons/ReportedNotice.vue';
import { useTargetLang } from '@/composables/useTargetLang';
import { t } from '@/i18n';
import type { StringKey } from '@/i18n';
import { ptBR } from '@/i18n/pt-BR';
import {
    countWords,
    isProcessing,
    shortDate,
    sourceLabel,
} from '@/lib/lessons';
import type {
    LessonDetail,
    LessonReview,
    ReviewConcept,
} from '@/types/lessons';

/**
 * Tela da aula (J2-F3, J2-F4, protótipo tela 5): progresso da geração e,
 * depois, a revisão com origem, edição, nova geração e "reportar erro".
 * Todo texto gerado é exibido escapado (IAS-02).
 */
const props = defineProps<{
    lesson: LessonDetail;
    review: LessonReview | null;
    flash?: string | null;
}>();

const SUMMARY = 'summary';
const MISTAKES = 'common_mistakes';
const SUMMARY_MAX_WORDS = 150;

const targetLang = useTargetLang();
const base = computed(() => `/aulas/${props.lesson.ulid}`);
const processing = computed(() => isProcessing(props.lesson.status));
const origin = computed(() => shortDate(props.lesson.lesson_date));

// Consulta só o estado da aula enquanto a fila trabalha (J2-B11).
const { start, stop } = usePoll(
    2000,
    { only: ['lesson', 'review'] },
    { autoStart: false },
);

// "Revisão pronta" aparece quando a geração termina com o aluno na tela.
const justFinished = ref(false);

watch(
    processing,
    (now, before) => {
        if (now) {
            start();
        } else {
            stop();
            justFinished.value =
                before === true && props.lesson.status === 'ready';
        }
    },
    { immediate: true },
);

// Avisos rápidos depois de cada ação.
const toast = ref<string | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | undefined;

function showToast(code: string | null | undefined): void {
    const messages: Record<string, string> = ptBR.review.flash;

    if (!code || !messages[code]) {
        return;
    }

    toast.value = messages[code];
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (toast.value = null), 4000);
}

let removeSuccessListener: VoidFunction | undefined;

onMounted(() => {
    showToast(props.flash);
    removeSuccessListener = router.on('success', (event) => {
        showToast(event.detail.page.props.flash as string | null);
    });
});

onBeforeUnmount(() => {
    removeSuccessListener?.();
    clearTimeout(toastTimer);
});

const languageLine = computed(() => {
    const name = targetLang.value.name;
    const language = name.charAt(0).toUpperCase() + name.slice(1);

    return props.lesson.level
        ? t('review.languageLevel', {
              language,
              level: props.lesson.level.toUpperCase(),
          })
        : language;
});

const summaryWords = computed(() => countWords(props.review?.summary ?? ''));

// Itens em análise (MAT-05).
const reportOf = (item: string) =>
    props.review?.open_reports.find((report) => report.item === item);
const conceptItem = (concept: ReviewConcept) => `concept:${concept.key}`;

// "Tentar de novo" e "Regenerar" podem ser recusados pelo limite (REV-04).
const actionError = ref<string | null>(null);
const retrying = ref(false);

function retry(): void {
    actionError.value = null;
    retrying.value = true;
    router.post(
        `${base.value}/tentar-de-novo`,
        {},
        {
            preserveScroll: true,
            onError: (errors) => (actionError.value = errors.lesson ?? null),
            onFinish: () => (retrying.value = false),
        },
    );
}

const busyUndo = ref<string | null>(null);

function undo(item: string): void {
    const report = reportOf(item);

    if (!report) {
        return;
    }

    busyUndo.value = item;
    router.delete(`${base.value}/reportes/${report.ulid}`, {
        preserveScroll: true,
        onFinish: () => (busyUndo.value = null),
    });
}

// "Você consegue?": salva na hora, como no protótipo.
const canDoDone = ref<number[]>([...(props.review?.can_do_done ?? [])]);

watch(
    () => props.review?.can_do_done,
    (done) => (canDoDone.value = [...(done ?? [])]),
);

function toggleCanDo(index: number, checked: boolean): void {
    canDoDone.value = checked
        ? [...canDoDone.value, index].sort((a, b) => a - b)
        : canDoDone.value.filter((value) => value !== index);

    router.put(
        `${base.value}/revisao/consigo`,
        { done: canDoDone.value },
        { preserveScroll: true, preserveState: true },
    );
}

// Folhas de edição, relatório, nova geração e remoção.
type SheetMode =
    | { kind: 'editLesson' }
    | { kind: 'editSummary' }
    | { kind: 'editConcept'; concept: ReviewConcept }
    | { kind: 'report'; item: string }
    | { kind: 'regenerate' }
    | { kind: 'remove' };

const sheet = ref<SheetMode | null>(null);
const sheetOpen = computed({
    get: () => sheet.value !== null,
    set: (open) => {
        if (!open) {
            sheet.value = null;
        }
    },
});

const sheetTitle = computed(() => {
    const titles: Record<SheetMode['kind'], StringKey> = {
        editLesson: 'review.editLessonTitle',
        editSummary: 'review.editSummaryTitle',
        editConcept: 'review.editConceptTitle',
        report: 'review.reportTitle',
        regenerate: 'review.regenTitle',
        remove: 'review.removeTitle',
    };

    return sheet.value ? t(titles[sheet.value.kind]) : '';
});

const lessonForm = useForm({ title: '', lesson_date: '' });
const textForm = useForm({ summary: '', explanation: '' });
const reportForm = useForm({
    item: '',
    reason: 'wrong_translation',
    details: '',
});
const actionForm = useForm({});

const reasons = [
    { value: 'wrong_translation', label: t('review.reasonWrongTranslation') },
    {
        value: 'confusing_explanation',
        label: t('review.reasonConfusingExplanation'),
    },
    { value: 'unnatural_example', label: t('review.reasonUnnaturalExample') },
    { value: 'other', label: t('review.reasonOther') },
];

function open(mode: SheetMode): void {
    lessonForm.clearErrors();
    textForm.clearErrors();
    reportForm.clearErrors();

    if (mode.kind === 'editLesson') {
        lessonForm.title = props.lesson.title;
        lessonForm.lesson_date = props.lesson.lesson_date;
    } else if (mode.kind === 'editSummary') {
        textForm.summary = props.review?.summary ?? '';
    } else if (mode.kind === 'editConcept') {
        textForm.explanation = mode.concept.explanation;
    } else if (mode.kind === 'report') {
        reportForm.item = mode.item;
        reportForm.reason = 'wrong_translation';
        reportForm.details = '';
    }

    sheet.value = mode;
}

const closeSheet = () => (sheet.value = null);
const keep = { preserveScroll: true, onSuccess: closeSheet };

function saveSheet(): void {
    const mode = sheet.value;

    if (!mode) {
        return;
    }

    switch (mode.kind) {
        case 'editLesson':
            lessonForm.patch(base.value, keep);
            break;
        case 'editSummary':
            textForm
                .transform((data) => ({ summary: data.summary }))
                .patch(`${base.value}/revisao`, keep);
            break;
        case 'editConcept':
            textForm
                .transform((data) => ({ explanation: data.explanation }))
                .patch(
                    `${base.value}/revisao/conceitos/${mode.concept.key}`,
                    keep,
                );
            break;
        case 'report':
            reportForm.post(`${base.value}/reportes`, keep);
            break;
        case 'regenerate':
            actionError.value = null;
            actionForm.post(`${base.value}/regenerar`, {
                preserveScroll: true,
                onError: (errors) =>
                    (actionError.value = errors.lesson ?? null),
                onFinish: closeSheet,
            });
            break;
        case 'remove':
            actionForm.delete(base.value);
            break;
    }
}

function removeConcept(concept: ReviewConcept): void {
    router.delete(`${base.value}/revisao/conceitos/${concept.key}`, {
        preserveScroll: true,
    });
}

const regenerationHint = computed(() => {
    if (!props.lesson.can_regenerate) {
        return t('review.noRegenerations');
    }

    return props.lesson.regenerations_left === 1
        ? t('review.regenerationsLeftOne')
        : t('review.regenerationsLeft', { n: props.lesson.regenerations_left });
});
</script>

<template>
    <Head :title="lesson.title" />

    <div class="flex flex-wrap items-center gap-2">
        <OriginTag :lesson-date="origin" />
        <span
            class="inline-flex items-center rounded-xs bg-sf2 px-2.25 py-1 text-[12.5px] font-bold text-ink2"
            >{{ languageLine }}</span
        >
        <span
            class="inline-flex items-center gap-1 rounded-xs bg-sf2 px-2.25 py-1 text-[12.5px] font-bold text-ink2"
        >
            <Icon
                :name="lesson.source === 'pdf' ? 'picture_as_pdf' : 'notes'"
                :size="15"
            />{{ sourceLabel(lesson.source, lesson.pages) }}
        </span>
    </div>
    <h1 class="m-0 type-display-l">{{ lesson.title }}</h1>

    <GenerationProgress v-if="processing" :status="lesson.status" />

    <LessonFailure
        v-if="lesson.status === 'failed'"
        :reason="lesson.failure_reason"
        :can-retry="lesson.can_retry"
        :retrying="retrying"
        @retry="retry"
    />

    <Banner v-if="actionError" tone="error" icon="error" :title="actionError" />

    <!-- Se uma nova geração falhar, a versão anterior continua visível. -->
    <template v-if="review && !processing">
        <div class="flex flex-wrap items-center gap-2">
            <Button
                :href="`/praticar/flashcards?aula=${lesson.ulid}`"
                icon="play_arrow"
                class="grow sm:grow-0"
                >{{ t('review.practice') }}</Button
            >
            <Button
                variant="secondary"
                icon="edit"
                @click="open({ kind: 'editLesson' })"
                >{{ t('review.edit') }}</Button
            >
            <Button
                variant="secondary"
                icon="refresh"
                :disabled="!lesson.can_regenerate"
                @click="open({ kind: 'regenerate' })"
                >{{ t('review.regenerate') }}</Button
            >
            <Button
                variant="secondary"
                :aria-label="t('review.removeLesson')"
                @click="open({ kind: 'remove' })"
                ><Icon name="delete"
            /></Button>
        </div>

        <Banner
            v-if="justFinished"
            tone="info"
            icon="check_circle"
            :title="t('review.readyTitle')"
            role="status"
            >{{ t('review.readyBody') }}</Banner
        >

        <div
            class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)]"
        >
            <div class="flex flex-col gap-6">
                <Card
                    as="section"
                    aria-labelledby="review-summary"
                    class="flex flex-col gap-3"
                >
                    <h2 id="review-summary" class="m-0 type-subtitle">
                        {{ t('review.summary') }}
                    </h2>
                    <ReportedNotice
                        v-if="reportOf(SUMMARY)"
                        :busy="busyUndo === SUMMARY"
                        @undo="undo(SUMMARY)"
                    />
                    <p class="m-0 whitespace-pre-line type-body">
                        {{ review.summary }}
                    </p>
                    <div
                        class="flex flex-wrap items-center gap-1 border-t border-line pt-3"
                    >
                        <span class="mr-auto text-sm text-ink2"
                            >{{ t('origin.lesson', { date: origin }) }} ·
                            {{
                                t('review.summaryWords', {
                                    n: summaryWords,
                                    max: SUMMARY_MAX_WORDS,
                                })
                            }}</span
                        >
                        <Button
                            variant="ghost"
                            icon="edit"
                            @click="open({ kind: 'editSummary' })"
                            >{{ t('review.edit') }}</Button
                        >
                        <Button
                            v-if="!reportOf(SUMMARY)"
                            variant="ghost"
                            icon="flag"
                            @click="open({ kind: 'report', item: SUMMARY })"
                            >{{ t('review.report') }}</Button
                        >
                    </div>
                </Card>

                <section
                    aria-labelledby="review-concepts"
                    class="flex flex-col gap-4"
                >
                    <h2 id="review-concepts" class="m-0 type-subtitle">
                        {{ t('review.concepts') }}
                    </h2>
                    <Card
                        v-for="(concept, index) in review.concepts"
                        :key="concept.key"
                        as="article"
                        :aria-labelledby="`concept-${concept.key}`"
                        class="flex flex-col gap-3"
                    >
                        <ReportedNotice
                            v-if="reportOf(conceptItem(concept))"
                            :busy="busyUndo === conceptItem(concept)"
                            @undo="undo(conceptItem(concept))"
                        />
                        <div class="flex items-start gap-3">
                            <span
                                class="grid size-9 shrink-0 place-items-center rounded-full bg-prit font-display text-lg text-prid"
                                aria-hidden="true"
                                >{{ index + 1 }}</span
                            >
                            <h3
                                :id="`concept-${concept.key}`"
                                class="m-0 pt-1 type-subtitle"
                            >
                                {{ concept.name }}
                            </h3>
                        </div>
                        <template v-if="!reportOf(conceptItem(concept))">
                            <p class="m-0 type-body">
                                {{ concept.explanation }}
                            </p>
                            <ul
                                class="m-0 flex list-none flex-col gap-2 p-0"
                                :aria-label="t('review.examples')"
                            >
                                <li
                                    v-for="(
                                        example, exampleIndex
                                    ) in concept.examples"
                                    :key="exampleIndex"
                                    class="flex flex-col gap-0.5 rounded-s bg-sf2 px-3.5 py-2.5"
                                >
                                    <TargetText>{{
                                        example.target
                                    }}</TargetText>
                                    <span class="text-sm text-ink2">{{
                                        example.translation
                                    }}</span>
                                </li>
                            </ul>
                            <div
                                class="flex flex-wrap items-center gap-1 border-t border-line pt-3"
                            >
                                <span class="mr-auto text-sm text-ink2">{{
                                    t('origin.lesson', { date: origin })
                                }}</span>
                                <Button
                                    variant="ghost"
                                    icon="edit"
                                    :aria-label="
                                        t('review.editConcept', {
                                            name: concept.name,
                                        })
                                    "
                                    @click="
                                        open({ kind: 'editConcept', concept })
                                    "
                                    >{{ t('review.edit') }}</Button
                                >
                                <Button
                                    variant="ghost"
                                    icon="delete"
                                    :aria-label="
                                        t('review.removeConcept', {
                                            name: concept.name,
                                        })
                                    "
                                    @click="removeConcept(concept)"
                                    >{{ t('review.remove') }}</Button
                                >
                                <Button
                                    variant="ghost"
                                    icon="flag"
                                    :aria-label="
                                        t('review.reportConcept', {
                                            name: concept.name,
                                        })
                                    "
                                    @click="
                                        open({
                                            kind: 'report',
                                            item: conceptItem(concept),
                                        })
                                    "
                                    >{{ t('review.report') }}</Button
                                >
                            </div>
                        </template>
                    </Card>
                </section>

                <Card
                    as="section"
                    aria-labelledby="review-mistakes"
                    class="flex flex-col gap-3"
                >
                    <h2 id="review-mistakes" class="m-0 type-subtitle">
                        {{ t('review.mistakes') }}
                    </h2>
                    <ReportedNotice
                        v-if="reportOf(MISTAKES)"
                        :busy="busyUndo === MISTAKES"
                        @undo="undo(MISTAKES)"
                    />
                    <ul v-else class="m-0 flex list-none flex-col gap-3 p-0">
                        <li
                            v-for="(mistake, index) in review.common_mistakes"
                            :key="index"
                            class="flex flex-col gap-1 rounded-s bg-sf2 px-3.5 py-3"
                        >
                            <span class="flex flex-wrap items-center gap-1.5">
                                <Icon
                                    name="close"
                                    :size="18"
                                    class="text-errd"
                                />
                                <span class="text-sm font-bold text-errd">{{
                                    t('review.wrong')
                                }}</span>
                                <TargetText class="line-through">{{
                                    mistake.wrong
                                }}</TargetText>
                            </span>
                            <span class="flex flex-wrap items-center gap-1.5">
                                <Icon
                                    name="check"
                                    :size="18"
                                    class="text-okd"
                                />
                                <span class="text-sm font-bold text-okd">{{
                                    t('review.right')
                                }}</span>
                                <TargetText>{{ mistake.right }}</TargetText>
                            </span>
                            <span class="text-sm text-ink2">{{
                                mistake.why
                            }}</span>
                        </li>
                    </ul>
                    <div
                        class="flex flex-wrap items-center gap-1 border-t border-line pt-3"
                    >
                        <span class="mr-auto text-sm text-ink2">{{
                            t('origin.lesson', { date: origin })
                        }}</span>
                        <Button
                            v-if="!reportOf(MISTAKES)"
                            variant="ghost"
                            icon="flag"
                            @click="open({ kind: 'report', item: MISTAKES })"
                            >{{ t('review.report') }}</Button
                        >
                    </div>
                </Card>
            </div>

            <aside class="flex flex-col gap-4 lg:sticky lg:top-6">
                <Card
                    as="section"
                    aria-labelledby="review-can-do"
                    class="flex flex-col gap-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h2 id="review-can-do" class="m-0 type-subtitle">
                            {{ t('review.canDo') }}
                        </h2>
                        <span class="text-sm font-bold text-ink2">{{
                            t('review.canDoCount', {
                                n: canDoDone.length,
                                total: review.can_do.length,
                            })
                        }}</span>
                    </div>
                    <p class="m-0 text-sm text-ink2">
                        {{ t('review.canDoHint') }}
                    </p>
                    <ul class="m-0 flex list-none flex-col gap-1 p-0">
                        <li v-for="(item, index) in review.can_do" :key="index">
                            <Checkbox
                                :model-value="canDoDone.includes(index)"
                                @update:model-value="
                                    (checked: boolean) =>
                                        toggleCanDo(index, checked)
                                "
                                >{{ item }}</Checkbox
                            >
                        </li>
                    </ul>
                </Card>
                <p class="m-0 flex items-start gap-2 text-sm text-ink2">
                    <Icon name="lock" :size="18" />{{ t('review.privacy') }}
                </p>
            </aside>
        </div>
    </template>

    <Sheet v-model:open="sheetOpen" :title="sheetTitle">
        <form
            v-if="sheet"
            class="flex flex-col gap-4"
            novalidate
            @submit.prevent="saveSheet"
        >
            <template v-if="sheet.kind === 'editLesson'">
                <TextField
                    v-model="lessonForm.title"
                    :label="t('upload.titleLabel')"
                    maxlength="120"
                    :error="lessonForm.errors.title"
                />
                <TextField
                    v-model="lessonForm.lesson_date"
                    type="date"
                    :label="t('upload.dateLabel')"
                    :error="lessonForm.errors.lesson_date"
                />
            </template>

            <template v-else-if="sheet.kind === 'editSummary'">
                <TextArea
                    v-model="textForm.summary"
                    :label="t('review.summary')"
                    :rows="7"
                    :error="textForm.errors.summary"
                    :hint="
                        t('review.summaryWords', {
                            n: countWords(textForm.summary),
                            max: SUMMARY_MAX_WORDS,
                        })
                    "
                />
                <p class="m-0 text-sm text-ink2">{{ t('review.editNote') }}</p>
            </template>

            <template v-else-if="sheet.kind === 'editConcept'">
                <TextArea
                    v-model="textForm.explanation"
                    :label="t('review.explanation')"
                    :rows="6"
                    :error="textForm.errors.explanation"
                />
                <p class="m-0 text-sm text-ink2">{{ t('review.editNote') }}</p>
            </template>

            <template v-else-if="sheet.kind === 'report'">
                <p class="m-0 text-ink2">{{ t('review.reportBody') }}</p>
                <fieldset class="m-0 flex flex-col gap-1 border-0 p-0">
                    <legend class="mb-1.5 p-0 text-sm font-bold">
                        {{ t('review.reportReasons') }}
                    </legend>
                    <label
                        v-for="reason in reasons"
                        :key="reason.value"
                        class="flex min-h-11 cursor-pointer items-center gap-3"
                    >
                        <input
                            v-model="reportForm.reason"
                            type="radio"
                            name="report-reason"
                            :value="reason.value"
                            class="m-0 size-5 accent-prid"
                        />
                        {{ reason.label }}
                    </label>
                </fieldset>
                <TextArea
                    v-model="reportForm.details"
                    :label="t('review.reportDetails')"
                    :placeholder="t('review.reportDetailsPlaceholder')"
                    :rows="3"
                    maxlength="500"
                    :error="reportForm.errors.details ?? reportForm.errors.item"
                />
            </template>

            <template v-else-if="sheet.kind === 'regenerate'">
                <p class="m-0 text-ink2">{{ t('review.regenBody') }}</p>
                <p class="m-0 text-sm text-ink2">{{ regenerationHint }}</p>
            </template>

            <template v-else-if="sheet.kind === 'remove'">
                <p class="m-0 text-ink2">{{ t('review.removeBody') }}</p>
            </template>

            <Button
                type="submit"
                block
                :variant="sheet.kind === 'remove' ? 'danger' : 'primary'"
                :icon="sheet.kind === 'regenerate' ? 'refresh' : undefined"
                :loading="
                    lessonForm.processing ||
                    textForm.processing ||
                    reportForm.processing ||
                    actionForm.processing
                "
                >{{
                    sheet.kind === 'report'
                        ? t('review.send')
                        : sheet.kind === 'regenerate'
                          ? t('review.regenerate')
                          : sheet.kind === 'remove'
                            ? t('review.removeLesson')
                            : t('review.save')
                }}</Button
            >
            <Button variant="ghost" block @click="closeSheet">{{
                t('review.cancel')
            }}</Button>
        </form>
    </Sheet>

    <div
        v-if="toast"
        class="pointer-events-none fixed inset-x-0 bottom-24 z-30 flex justify-center px-4 lg:bottom-8"
    >
        <Toast tone="info">{{ toast }}</Toast>
    </div>
</template>
