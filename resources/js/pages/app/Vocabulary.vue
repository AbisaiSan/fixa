<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import Checkbox from '@/components/fixa/Checkbox.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import Icon from '@/components/fixa/Icon.vue';
import PageState from '@/components/fixa/PageState.vue';
import type { PageStatus } from '@/components/fixa/PageState.vue';
import Select from '@/components/fixa/Select.vue';
import Sheet from '@/components/fixa/Sheet.vue';
import StatusChip from '@/components/fixa/StatusChip.vue';
import type { StatusKind } from '@/components/fixa/StatusChip.vue';
import TargetText from '@/components/fixa/TargetText.vue';
import TextField from '@/components/fixa/TextField.vue';
import Toast from '@/components/fixa/Toast.vue';
import ReportSheet from '@/components/lessons/ReportSheet.vue';
import { useTargetLang } from '@/composables/useTargetLang';
import { t } from '@/i18n';
import { ptBR } from '@/i18n/pt-BR';
import { shortDate } from '@/lib/lessons';
import type { WordDetail, WordListItem, WordState } from '@/types/lessons';

/**
 * Vocabulário (J3-F5, protótipo tela 7, VOC-01): busca sem acentos,
 * filtros por estado e por aula, detalhe ao lado no desktop e em tela
 * própria no celular, e "Adicionar palavra" (VOC-03).
 */
type Filters = { q: string; estado: string; aula: string; origem: string };

const props = defineProps<{
    status: PageStatus;
    filters?: Filters;
    counts?: Record<'all' | WordState, number>;
    words?: WordListItem[];
    lessons?: { ulid: string; title: string; lesson_date: string }[];
    selected?: WordDetail | null;
    aiLeft?: number;
    flash?: string | null;
}>();

const targetLang = useTargetLang();
const tab = ref<'mine' | 'common'>('mine');

const stateChip: Record<WordState, StatusKind> = {
    new: 'nova',
    learning: 'aprendendo',
    mastered: 'dominada',
};

// Filtros na URL, para voltar ao mesmo lugar (e funcionar no celular).
const query = ref(props.filters?.q ?? '');
const filters = computed<Filters>(() => ({
    q: props.filters?.q ?? '',
    estado: props.filters?.estado ?? '',
    aula: props.filters?.aula ?? '',
    origem: props.filters?.origem ?? '',
}));

function params(changes: Partial<Filters>): Record<string, string> {
    return Object.fromEntries(
        Object.entries({ ...filters.value, ...changes }).filter(
            ([, value]) => value !== '',
        ),
    );
}

function apply(changes: Partial<Filters>): void {
    router.get('/vocabulario', params(changes), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

let searchTimer: ReturnType<typeof setTimeout> | undefined;

watch(query, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => apply({ q: value.trim() }), 300);
});

const chips = computed(() =>
    (
        [
            ['', 'vocabulary.all', 'apps', props.counts?.all ?? 0],
            [
                'new',
                'vocabulary.new',
                'radio_button_unchecked',
                props.counts?.new ?? 0,
            ],
            [
                'learning',
                'vocabulary.learning',
                'contrast',
                props.counts?.learning ?? 0,
            ],
            [
                'mastered',
                'vocabulary.mastered',
                'check_circle',
                props.counts?.mastered ?? 0,
            ],
        ] as const
    ).map(([value, key, icon, n]) => ({
        value,
        icon,
        label: t('vocabulary.chip', { label: t(key), n }),
        active: filters.value.estado === value,
    })),
);

const lessonValue = computed(() =>
    filters.value.origem === 'mine' ? 'mine' : filters.value.aula,
);
const lessonOptions = computed(() => [
    { value: '', label: t('vocabulary.allLessons') },
    ...(props.lessons ?? []).map((lesson) => ({
        value: lesson.ulid,
        label: t('vocabulary.lessonOption', {
            date: shortDate(lesson.lesson_date),
        }),
    })),
    { value: 'mine', label: t('vocabulary.mineOption') },
]);

function pickLesson(value: string): void {
    apply(
        value === 'mine'
            ? { aula: '', origem: 'mine' }
            : { aula: value, origem: '' },
    );
}

function clearFilters(): void {
    query.value = '';
    apply({ q: '', estado: '', aula: '', origem: '' });
}

const words = computed(() => props.words ?? []);
const countLabel = computed(() =>
    words.value.length === 1
        ? t('vocabulary.countOne')
        : t('vocabulary.count', { n: words.value.length }),
);

const wordHref = (word: { ulid: string }) => {
    const search = new URLSearchParams(params({})).toString();

    return `/vocabulario/${word.ulid}${search ? `?${search}` : ''}`;
};

const listHref = computed(() => {
    const search = new URLSearchParams(params({})).toString();

    return `/vocabulario${search ? `?${search}` : ''}`;
});

// Avisos depois das ações.
const toast = ref<string | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | undefined;
let removeListener: VoidFunction | undefined;

function showToast(code: string | null | undefined): void {
    const messages: Record<string, string> = ptBR.vocabulary.flash;

    if (!code || !messages[code]) {
        return;
    }

    toast.value = messages[code];
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (toast.value = null), 4000);
}

onMounted(() => {
    showToast(props.flash);
    removeListener = router.on('success', (event) =>
        showToast(event.detail.page.props.flash as string | null),
    );
});

onBeforeUnmount(() => {
    removeListener?.();
    clearTimeout(toastTimer);
    clearTimeout(searchTimer);
});

// Adicionar, editar e reportar.
const partsOfSpeech = ptBR.vocabulary.partsOfSpeech
    .split(',')
    .map((value) => ({ value, label: value }));

const addOpen = ref(false);
const addForm = useForm({
    term: '',
    translation: '',
    part_of_speech: 'expressão',
    fill_with_ai: false,
});

function addWord(): void {
    addForm.post('/vocabulario', {
        preserveScroll: true,
        onSuccess: () => {
            addOpen.value = false;
            addForm.reset();
        },
    });
}

const editOpen = ref(false);
const editForm = useForm({
    translation: '',
    part_of_speech: '',
    definition: '',
});

function openEdit(): void {
    if (!props.selected) {
        return;
    }

    editForm.translation = props.selected.translation;
    editForm.part_of_speech = props.selected.part_of_speech ?? '';
    editForm.definition = props.selected.definition ?? '';
    editForm.clearErrors();
    editOpen.value = true;
}

function saveEdit(): void {
    if (!props.selected) {
        return;
    }

    editForm.patch(`/vocabulario/${props.selected.ulid}`, {
        preserveScroll: true,
        onSuccess: () => (editOpen.value = false),
    });
}

const reportOpen = ref(false);

function undoReport(): void {
    if (props.selected?.report) {
        router.delete(
            `/vocabulario/${props.selected.ulid}/reportes/${props.selected.report}`,
            { preserveScroll: true },
        );
    }
}

const practiceHref = computed(() =>
    props.selected?.lesson
        ? `/praticar/flashcards?aula=${props.selected.lesson.ulid}`
        : '/praticar/flashcards',
);
</script>

<template>
    <Head :title="t('nav.vocabulary')" />

    <h1 class="m-0 type-display-l">{{ t('nav.vocabulary') }}</h1>

    <div
        role="tablist"
        :aria-label="t('vocabulary.listsLabel')"
        class="inline-flex self-start rounded-m bg-sf2 p-1"
    >
        <button
            v-for="item in [
                { value: 'mine', label: t('vocabulary.mine') },
                { value: 'common', label: t('vocabulary.common') },
            ] as const"
            :key="item.value"
            role="tab"
            type="button"
            :aria-selected="tab === item.value"
            class="inline-flex min-h-11 items-center gap-2 rounded-s px-4 font-bold"
            :class="
                tab === item.value ? 'bg-sf text-ink shadow-sh1' : 'text-ink2'
            "
            @click="tab = item.value"
        >
            {{ item.label }}
            <span
                v-if="item.value === 'common'"
                class="rounded-full bg-infot px-2 py-0.5 text-[11.5px] font-bold text-infod"
                >{{ t('vocabulary.phase2') }}</span
            >
        </button>
    </div>

    <Card v-if="tab === 'common'">
        <EmptyState icon="format_list_numbered" :title="t('vocabulary.common')">
            {{ t('vocabulary.commonSoon') }}
        </EmptyState>
    </Card>

    <PageState v-else :status="status">
        <template #empty>
            <Card>
                <EmptyState
                    icon="translate"
                    :title="t('empty.vocabularyTitle')"
                >
                    {{ t('empty.vocabularyBody') }}
                    <template #action>
                        <div class="mt-2 flex flex-wrap justify-center gap-2">
                            <Button href="/aulas/enviar" variant="secondary">{{
                                t('empty.uploadAction')
                            }}</Button>
                            <Button
                                variant="secondary"
                                icon="add"
                                @click="addOpen = true"
                                >{{ t('vocabulary.add') }}</Button
                            >
                        </div>
                    </template>
                </EmptyState>
            </Card>
        </template>

        <div
            class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]"
        >
            <section
                class="flex-col gap-4"
                :class="selected ? 'hidden lg:flex' : 'flex'"
                :aria-label="t('vocabulary.mine')"
            >
                <label class="relative block">
                    <span class="sr-only">{{ t('vocabulary.search') }}</span>
                    <Icon
                        name="search"
                        class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-ink2"
                    />
                    <input
                        v-model="query"
                        type="search"
                        :placeholder="
                            t('vocabulary.searchPlaceholder', {
                                language: targetLang.name,
                            })
                        "
                        class="min-h-12.5 w-full rounded-s border-[1.5px] border-line bg-sf pr-3.5 pl-11 text-base text-ink placeholder:text-ink3 focus:border-prid focus:shadow-[0_0_0_3px_var(--prit)] focus:outline-none"
                    />
                </label>

                <div
                    role="group"
                    :aria-label="t('vocabulary.stateFilter')"
                    class="flex flex-wrap gap-2"
                >
                    <button
                        v-for="chip in chips"
                        :key="chip.value"
                        type="button"
                        :aria-pressed="chip.active"
                        class="inline-flex min-h-10 items-center gap-1.5 rounded-full border-[1.5px] px-3.5 text-sm font-bold"
                        :class="
                            chip.active
                                ? 'border-prid bg-prit text-prid'
                                : 'border-line bg-sf text-ink2'
                        "
                        @click="apply({ estado: chip.value })"
                    >
                        <Icon :name="chip.icon" :size="17" />{{ chip.label }}
                    </button>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <Select
                        :model-value="lessonValue"
                        :label="t('vocabulary.lessonFilter')"
                        :options="lessonOptions"
                        class="min-w-50 flex-1"
                        @update:model-value="pickLesson"
                    />
                    <Button
                        variant="secondary"
                        icon="add"
                        @click="addOpen = true"
                        >{{ t('vocabulary.add') }}</Button
                    >
                </div>

                <p class="m-0 text-sm text-ink2" aria-live="polite">
                    {{ countLabel }}
                </p>

                <div
                    v-if="words.length === 0"
                    class="flex flex-col items-start gap-2 rounded-m bg-sf2 p-4"
                >
                    <p class="m-0">{{ t('vocabulary.none') }}</p>
                    <Button variant="ghost" @click="clearFilters">{{
                        t('vocabulary.clear')
                    }}</Button>
                </div>

                <ul v-else class="m-0 flex list-none flex-col gap-2 p-0">
                    <li v-for="word in words" :key="word.ulid">
                        <Link
                            :href="wordHref(word)"
                            preserve-scroll
                            :aria-current="
                                selected?.ulid === word.ulid
                                    ? 'true'
                                    : undefined
                            "
                            class="flex items-center gap-3 rounded-m border bg-sf p-3.5 text-ink no-underline hover:border-ink3"
                            :class="
                                selected?.ulid === word.ulid
                                    ? 'border-2 border-prid'
                                    : 'border-line'
                            "
                        >
                            <span class="flex min-w-0 flex-1 flex-col">
                                <span
                                    class="flex flex-wrap items-baseline gap-2"
                                >
                                    <TargetText class="font-bold">{{
                                        word.term
                                    }}</TargetText>
                                    <span
                                        v-if="word.part_of_speech"
                                        class="text-[12.5px] text-ink2"
                                        >{{ word.part_of_speech }}</span
                                    >
                                </span>
                                <span class="text-sm text-ink2">{{
                                    word.translation
                                }}</span>
                            </span>
                            <StatusChip :kind="stateChip[word.state]" />
                        </Link>
                    </li>
                </ul>
            </section>

            <Card
                v-if="selected"
                as="article"
                aria-labelledby="word-title"
                class="flex flex-col gap-4 lg:sticky lg:top-6"
            >
                <Button
                    :href="listHref"
                    variant="ghost"
                    icon="arrow_back"
                    class="self-start lg:hidden"
                    >{{ t('vocabulary.back') }}</Button
                >

                <div
                    v-if="selected.report"
                    role="status"
                    class="flex flex-wrap items-center gap-3 rounded-m bg-infot px-3.5 py-3"
                >
                    <Icon name="flag" class="text-infod" />
                    <span class="min-w-0 flex-1 text-sm"
                        ><strong>{{ t('vocabulary.reportedTitle') }}</strong>
                        {{ t('vocabulary.reportedBody') }}</span
                    >
                    <Button variant="ghost" @click="undoReport">{{
                        t('vocabulary.undo')
                    }}</Button>
                </div>

                <div class="flex flex-col gap-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            v-if="selected.part_of_speech"
                            class="text-[12.5px] font-bold tracking-wide text-ink2 uppercase"
                            >{{ selected.part_of_speech }}</span
                        >
                        <StatusChip :kind="stateChip[selected.state]" />
                    </div>
                    <h2 id="word-title" class="m-0">
                        <TargetText size="large" class="type-target">{{
                            selected.term
                        }}</TargetText>
                    </h2>
                    <p class="m-0 type-subtitle">{{ selected.translation }}</p>
                </div>

                <p v-if="selected.definition" class="m-0 type-body">
                    {{ selected.definition }}
                </p>

                <section v-if="selected.examples.length > 0">
                    <h3 class="mt-0 mb-2 type-label text-ink2">
                        {{ t('vocabulary.examples') }}
                    </h3>
                    <ul class="m-0 flex list-none flex-col gap-2 p-0">
                        <li
                            v-for="(example, index) in selected.examples"
                            :key="index"
                            class="flex flex-col gap-0.5 rounded-s bg-sf2 px-3.5 py-2.5"
                        >
                            <TargetText>{{ example.target }}</TargetText>
                            <span class="text-sm text-ink2">{{
                                example.translation
                            }}</span>
                        </li>
                    </ul>
                </section>

                <section v-if="selected.collocations.length > 0">
                    <h3 class="mt-0 mb-2 type-label text-ink2">
                        {{ t('vocabulary.collocations') }}
                    </h3>
                    <ul class="m-0 flex list-none flex-wrap gap-2 p-0">
                        <li
                            v-for="item in selected.collocations"
                            :key="item"
                            class="rounded-full bg-sf2 px-3 py-1 text-sm"
                        >
                            <TargetText>{{ item }}</TargetText>
                        </li>
                    </ul>
                </section>

                <p class="m-0 flex items-center gap-1.5 text-sm text-ink2">
                    <Icon
                        :name="selected.lesson ? 'menu_book' : 'person'"
                        :size="18"
                    />{{
                        selected.lesson
                            ? t('vocabulary.fromLesson', {
                                  date: shortDate(selected.lesson.lesson_date),
                              })
                            : t('vocabulary.fromMe')
                    }}
                </p>

                <div
                    class="flex flex-wrap items-center gap-2 border-t border-line pt-3"
                >
                    <Button :href="practiceHref" icon="style">{{
                        t('vocabulary.practice')
                    }}</Button>
                    <Button variant="secondary" icon="edit" @click="openEdit">{{
                        t('vocabulary.edit')
                    }}</Button>
                    <Button
                        v-if="selected.generated && !selected.report"
                        variant="ghost"
                        icon="flag"
                        class="ml-auto"
                        @click="reportOpen = true"
                        >{{ t('vocabulary.report') }}</Button
                    >
                </div>
            </Card>
        </div>
    </PageState>

    <Sheet v-model:open="addOpen" :title="t('vocabulary.addTitle')">
        <form class="flex flex-col gap-4" novalidate @submit.prevent="addWord">
            <TextField
                v-model="addForm.term"
                :label="t('vocabulary.term', { language: targetLang.name })"
                :placeholder="t('vocabulary.termPlaceholder')"
                :lang="targetLang.code"
                maxlength="120"
                :error="addForm.errors.term"
            />
            <TextField
                v-model="addForm.translation"
                :label="t('vocabulary.translation')"
                :placeholder="t('vocabulary.translationPlaceholder')"
                maxlength="200"
                :hint="
                    addForm.fill_with_ai
                        ? t('vocabulary.translationHint')
                        : undefined
                "
                :error="addForm.errors.translation"
            />
            <Select
                v-model="addForm.part_of_speech"
                :label="t('vocabulary.partOfSpeech')"
                :options="partsOfSpeech"
            />
            <div>
                <Checkbox
                    v-model="addForm.fill_with_ai"
                    :disabled="(aiLeft ?? 0) === 0"
                    >{{ t('vocabulary.fillWithAi') }}</Checkbox
                >
                <p class="mt-1 mb-0 text-[13px] text-ink2">
                    {{
                        (aiLeft ?? 0) === 0
                            ? t('vocabulary.aiNone')
                            : t('vocabulary.aiLeft', { n: aiLeft ?? 0 })
                    }}
                </p>
            </div>
            <p class="m-0 flex items-center gap-1.5 text-sm text-ink2">
                <Icon name="person" :size="18" />{{ t('vocabulary.addNote') }}
            </p>
            <Button type="submit" block :loading="addForm.processing">{{
                t('vocabulary.add')
            }}</Button>
            <Button variant="ghost" block @click="addOpen = false">{{
                t('vocabulary.cancel')
            }}</Button>
        </form>
    </Sheet>

    <Sheet v-model:open="editOpen" :title="t('vocabulary.editTitle')">
        <form class="flex flex-col gap-4" novalidate @submit.prevent="saveEdit">
            <TextField
                v-model="editForm.translation"
                :label="t('vocabulary.translation')"
                maxlength="200"
                :error="editForm.errors.translation"
            />
            <Select
                v-model="editForm.part_of_speech"
                :label="t('vocabulary.partOfSpeech')"
                :options="[{ value: '', label: '' }, ...partsOfSpeech]"
            />
            <TextField
                v-model="editForm.definition"
                :label="t('vocabulary.definition')"
                maxlength="300"
                :error="editForm.errors.definition"
            />
            <Button type="submit" block :loading="editForm.processing">{{
                t('vocabulary.save')
            }}</Button>
            <Button variant="ghost" block @click="editOpen = false">{{
                t('vocabulary.cancel')
            }}</Button>
        </form>
    </Sheet>

    <ReportSheet
        v-if="selected"
        v-model:open="reportOpen"
        :url="`/vocabulario/${selected.ulid}/reportes`"
    />

    <div
        v-if="toast"
        class="pointer-events-none fixed inset-x-0 bottom-24 z-30 flex justify-center px-4 lg:bottom-8"
    >
        <Toast tone="info">{{ toast }}</Toast>
    </div>
</template>
