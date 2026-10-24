<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import Icon from '@/components/fixa/Icon.vue';
import ReportButton from '@/components/fixa/ReportButton.vue';
import Sheet from '@/components/fixa/Sheet.vue';
import ReportSheet from '@/components/lessons/ReportSheet.vue';
import NextBlockActions from '@/components/plan/NextBlockActions.vue';
import TargetText from '@/components/fixa/TargetText.vue';
import { useTargetLang } from '@/composables/useTargetLang';
import { t } from '@/i18n';
import { postJson } from '@/lib/http';
import { countWords, shortDate } from '@/lib/lessons';
import type { PracticeAttempt, PracticeItem } from '@/types/practice';

/**
 * Leitura (J4-F5, protótipo tela 9, ATV-04): texto no idioma estudado com
 * as palavras do glossário clicáveis, Sheet para salvar no vocabulário com
 * um toque e perguntas corrigidas pelo gabarito.
 */
type GlossaryEntry = {
    form: string;
    term: string;
    part_of_speech: string;
    translation: string;
    known: boolean;
};

const props = defineProps<{
    session: {
        ulid: string;
        title: string;
        paragraphs: string[];
        lesson: { ulid: string; lesson_date: string } | null;
    };
    glossary: GlossaryEntry[];
    questions: PracticeItem[];
}>();

const targetLang = useTargetLang();
const words = computed(() => countWords(props.session.paragraphs.join(' ')));

// Divide cada parágrafo em trechos, marcando as palavras do glossário.
const byForm = computed(
    () =>
        new Map(
            props.glossary.map((entry) => [entry.form.toLowerCase(), entry]),
        ),
);

type Segment = { text: string; entry?: GlossaryEntry };

function segments(paragraph: string): Segment[] {
    return paragraph
        .split(/([\p{L}'’-]+)/u)
        .filter((part) => part !== '')
        .map((part) => ({
            text: part,
            entry: byForm.value.get(part.toLowerCase()),
        }));
}

const selected = ref<GlossaryEntry | null>(null);
const sheetOpen = computed({
    get: () => selected.value !== null,
    set: (open) => {
        if (!open) {
            selected.value = null;
        }
    },
});
const saved = ref<string[]>(
    props.glossary.filter((entry) => entry.known).map((entry) => entry.form),
);
const saving = ref(false);
const saveError = ref(false);

async function save(): Promise<void> {
    const entry = selected.value;

    if (!entry || saving.value) {
        return;
    }

    saving.value = true;
    saveError.value = false;

    try {
        await postJson(`/praticar/leitura/${props.session.ulid}/palavras`, {
            form: entry.form,
        });
        saved.value.push(entry.form);
    } catch {
        saveError.value = true;
    } finally {
        saving.value = false;
    }
}

// Perguntas: escolha e "Verificar", com a correção logo abaixo.
const choices = ref<Record<string, string>>({});
const results = ref<Record<string, PracticeAttempt>>(
    Object.fromEntries(
        props.questions
            .filter((question) => question.attempt)
            .map((question) => [
                question.ulid,
                question.attempt as PracticeAttempt,
            ]),
    ),
);

async function check(question: PracticeItem): Promise<void> {
    const answer = choices.value[question.ulid];

    if (!answer) {
        return;
    }

    try {
        const result = await postJson<PracticeAttempt>(
            `/praticar/itens/${question.ulid}/respostas`,
            { answer },
        );
        results.value[question.ulid] = { ...result, answer };
    } catch {
        saveError.value = true;
    }
}

// "Reportar erro" nas perguntas geradas (J4-F8, MAT-05).
const reporting = ref<string | null>(null);
const reportOpen = computed({
    get: () => reporting.value !== null,
    set: (open) => {
        if (!open) {
            reporting.value = null;
        }
    },
});

function undo(question: PracticeItem): void {
    if (question.report) {
        router.delete(
            `/praticar/itens/${question.ulid}/reportes/${question.report}`,
            {
                preserveScroll: true,
            },
        );
    }
}

const score = computed(() => {
    const answered = Object.values(results.value);

    return answered.length === props.questions.length
        ? t('reading.score', {
              n: answered.filter((result) => result.verdict === 'total').length,
              total: props.questions.length,
          })
        : null;
});
</script>

<template>
    <Head :title="session.title || t('reading.title')" />

    <Button
        href="/praticar"
        variant="ghost"
        icon="arrow_back"
        class="self-start"
        >{{ t('reading.back') }}</Button
    >

    <div
        class="flex flex-wrap items-center gap-2 text-[12.5px] font-bold text-ink2"
    >
        <span v-if="session.lesson" class="rounded-xs bg-sf2 px-2.25 py-1">{{
            t('reading.fromLesson', {
                date: shortDate(session.lesson.lesson_date),
            })
        }}</span>
        <span class="rounded-xs bg-sf2 px-2.25 py-1">{{
            t('reading.minutes', {
                words,
                minutes: Math.max(1, Math.round(words / 50)),
            })
        }}</span>
    </div>

    <h1 class="m-0">
        <TargetText size="large" class="type-display-l">{{
            session.title
        }}</TargetText>
    </h1>
    <p class="m-0 flex items-center gap-1.5 text-sm text-ink2">
        <Icon name="touch_app" :size="18" />{{ t('reading.tapHint') }}
    </p>

    <Card as="article" class="flex flex-col gap-4">
        <TargetText
            v-for="(paragraph, index) in session.paragraphs"
            :key="index"
            as="p"
            class="m-0 text-lg leading-relaxed"
        >
            <template
                v-for="(segment, part) in segments(paragraph)"
                :key="part"
            >
                <button
                    v-if="segment.entry"
                    type="button"
                    class="rounded-xs border-b-2 border-dotted px-0.5 font-semibold"
                    :class="
                        saved.includes(segment.entry.form)
                            ? 'border-okd text-ink'
                            : 'border-prid text-prid hover:bg-prit'
                    "
                    :aria-label="t('reading.wordLabel', { word: segment.text })"
                    @click="selected = segment.entry ?? null"
                >
                    {{ segment.text }}</button
                ><template v-else>{{ segment.text }}</template>
            </template>
        </TargetText>
    </Card>

    <section aria-labelledby="reading-questions" class="flex flex-col gap-4">
        <h2 id="reading-questions" class="m-0 type-subtitle">
            {{ t('reading.questions') }}
        </h2>
        <Card
            v-for="question in questions"
            :key="question.ulid"
            as="fieldset"
            class="m-0 flex flex-col gap-2"
        >
            <legend class="sr-only">{{ question.sentence }}</legend>
            <TargetText as="p" class="m-0 font-bold">{{
                question.sentence
            }}</TargetText>
            <label
                v-for="option in question.options ?? []"
                :key="option"
                class="flex min-h-11 cursor-pointer items-center gap-3 rounded-s border-[1.5px] px-3 py-1.5"
                :class="
                    results[question.ulid]
                        ? option === results[question.ulid].correction.corrected
                            ? 'border-okd bg-okt'
                            : option === results[question.ulid].answer
                              ? 'border-errd bg-errt'
                              : 'border-line opacity-70'
                        : 'border-line'
                "
            >
                <input
                    v-model="choices[question.ulid]"
                    type="radio"
                    :name="`question-${question.ulid}`"
                    :value="option"
                    :disabled="!!results[question.ulid]"
                    class="m-0 size-5 accent-prid"
                />
                <TargetText>{{ option }}</TargetText>
                <span
                    v-if="
                        results[question.ulid] &&
                        option === results[question.ulid].correction.corrected
                    "
                    class="ml-auto text-sm font-bold text-okd"
                    >{{ t('feedback.correct') }}</span
                >
            </label>
            <Button
                v-if="!results[question.ulid]"
                variant="secondary"
                class="self-start"
                :disabled="!choices[question.ulid]"
                @click="check(question)"
                >{{ t('reading.check') }}</Button
            >
            <p
                v-else
                role="status"
                class="m-0 font-bold"
                :class="
                    results[question.ulid].verdict === 'total'
                        ? 'text-okd'
                        : 'text-errd'
                "
            >
                {{
                    results[question.ulid].verdict === 'total'
                        ? t('feedback.totalChoice')
                        : t('feedback.wrong')
                }}
            </p>
            <ReportButton
                class="self-start"
                :reported="!!question.report"
                :label="t('reading.reportQuestion', { n: question.position })"
                @report="reporting = question.ulid"
                @undo="undo(question)"
            />
        </Card>
        <p v-if="score" role="status" class="m-0 type-subtitle">{{ score }}</p>
        <NextBlockActions
            v-if="score"
            current="reading"
            fallback-href="/praticar"
            :fallback-label="t('nextBlock.backPractice')"
        />
    </section>

    <Sheet
        v-model:open="sheetOpen"
        :title="selected?.term ?? ''"
        :description="selected?.part_of_speech"
    >
        <div v-if="selected" class="flex flex-col gap-4">
            <p class="m-0 type-title">{{ selected.translation }}</p>
            <p
                v-if="saved.includes(selected.form)"
                role="status"
                class="m-0 flex items-center gap-1.5 font-bold text-okd"
            >
                <Icon name="check_circle" filled />{{
                    selected.known ? t('reading.known') : t('reading.saved')
                }}
            </p>
            <Button
                v-else
                block
                icon="bookmark_add"
                :loading="saving"
                @click="save"
                >{{ t('reading.save') }}</Button
            >
            <p v-if="saveError" role="alert" class="m-0 text-sm text-errd">
                {{ t('reading.saveError') }}
            </p>
            <p class="sr-only" :lang="targetLang.code">{{ selected.form }}</p>
        </div>
    </Sheet>

    <ReportSheet
        v-if="reporting"
        v-model:open="reportOpen"
        :url="`/praticar/itens/${reporting}/reportes`"
    />
</template>
