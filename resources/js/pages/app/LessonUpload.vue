<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { TabsContent, TabsList, TabsRoot, TabsTrigger } from 'reka-ui';
import { computed, ref, useTemplateRef } from 'vue';
import Banner from '@/components/fixa/Banner.vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import Checkbox from '@/components/fixa/Checkbox.vue';
import Icon from '@/components/fixa/Icon.vue';
import Select from '@/components/fixa/Select.vue';
import TextArea from '@/components/fixa/TextArea.vue';
import TextField from '@/components/fixa/TextField.vue';
import { useTargetLang } from '@/composables/useTargetLang';
import { languageName, t } from '@/i18n';
import { focusFirstError } from '@/lib/forms';
import { countWords } from '@/lib/lessons';
import type { LessonSource } from '@/types/lessons';

/**
 * Envio de aula (J2-F2, protótipo tela 4, MAT-02, MAT-04, MAT-07): texto
 * colado ou PDF com texto. Um erro de envio nunca apaga o que foi preenchido.
 */
const props = defineProps<{
    limits: { max_megabytes: number; max_pages: number; min_words: number };
    upcomingLanguages: string[];
}>();

const targetLang = useTargetLang();
const formEl = useTemplateRef<HTMLFormElement>('formEl');
const pdfInput = useTemplateRef<HTMLInputElement>('pdfInput');

const form = useForm<{
    source: LessonSource;
    title: string;
    lesson_date: string;
    text: string;
    pdf: File | null;
    rights: boolean;
}>({
    source: 'text',
    title: '',
    lesson_date: localToday(),
    text: '',
    pdf: null,
    rights: false,
});

// Problema de rede ou do servidor, fora da validação dos campos (J2-F5).
const sendProblem = ref<'network' | 'too_many' | null>(null);
const clientErrors = ref<{ text?: string; pdf?: string }>({});

const words = computed(() => countWords(form.text));
const missingWords = computed(() =>
    words.value > 0 && words.value < props.limits.min_words
        ? props.limits.min_words - words.value
        : 0,
);
const wordsLabel = computed(() =>
    words.value === 1
        ? t('upload.wordsOne', { min: props.limits.min_words })
        : t('upload.words', { n: words.value, min: props.limits.min_words }),
);

const languageOptions = computed(() => [
    { value: targetLang.value.code, label: capitalize(targetLang.value.name) },
    ...props.upcomingLanguages
        .filter((code) => code !== targetLang.value.code)
        .map((code) => ({
            value: code,
            label: t('settings.upcoming', {
                name: capitalize(languageName(code)),
            }),
            disabled: true,
        })),
]);
const language = ref(targetLang.value.code);

const pdfMegabytes = computed(() =>
    form.pdf
        ? (form.pdf.size / 1024 / 1024).toLocaleString('pt-BR', {
              maximumFractionDigits: 1,
          })
        : '',
);

function localToday(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

function capitalize(text: string): string {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

function pickPdf(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    clientErrors.value.pdf = undefined;
    form.clearErrors('pdf');

    if (file && file.size > props.limits.max_megabytes * 1024 * 1024) {
        clientErrors.value.pdf = t('upload.pdfTooBig', {
            mb: props.limits.max_megabytes,
            pages: props.limits.max_pages,
        });
        form.pdf = null;
        resetPdfInput();

        return;
    }

    form.pdf = file;
}

function removePdf(): void {
    form.pdf = null;
    resetPdfInput();
    pdfInput.value?.focus();
}

function resetPdfInput(): void {
    if (pdfInput.value) {
        pdfInput.value.value = '';
    }
}

function validateLocally(): boolean {
    clientErrors.value = {};

    if (form.source === 'text') {
        if (words.value === 0) {
            clientErrors.value.text = t('upload.textRequired');
        } else if (missingWords.value > 0) {
            clientErrors.value.text = t('upload.wordsMissing', {
                n: missingWords.value,
                min: props.limits.min_words,
            });
        }
    } else if (!form.pdf) {
        clientErrors.value.pdf = t('upload.pdfRequired');
    }

    return Object.keys(clientErrors.value).length === 0;
}

function submit(): void {
    sendProblem.value = null;

    if (!validateLocally()) {
        focusFirstError(formEl.value);

        return;
    }

    form.transform((data) => ({
        source: data.source,
        title: data.title,
        lesson_date: data.lesson_date,
        rights: data.rights ? '1' : '',
        ...(data.source === 'text' ? { text: data.text } : { pdf: data.pdf }),
    })).post('/aulas', {
        forceFormData: true,
        onError: () => focusFirstError(formEl.value),
        onNetworkError: () => {
            sendProblem.value = 'network';

            return false;
        },
        onHttpException: (response) => {
            sendProblem.value =
                response.status === 429 ? 'too_many' : 'network';

            return false;
        },
    });
}
</script>

<template>
    <Head :title="t('upload.title')" />

    <h1 class="m-0 type-display-l">{{ t('upload.title') }}</h1>

    <Card class="flex flex-col gap-5">
        <div>
            <h2 class="m-0 type-title">{{ t('upload.heading') }}</h2>
            <p class="mt-1 mb-0 text-ink2">{{ t('upload.intro') }}</p>
        </div>

        <Banner
            v-if="sendProblem"
            tone="error"
            icon="error"
            :title="
                sendProblem === 'too_many'
                    ? t('upload.tooManyTitle')
                    : t('upload.networkTitle')
            "
            >{{
                sendProblem === 'too_many'
                    ? t('upload.tooManyBody')
                    : t('upload.networkBody')
            }}</Banner
        >

        <form
            ref="formEl"
            class="flex flex-col gap-5"
            novalidate
            @submit.prevent="submit"
        >
            <TabsRoot v-model="form.source" class="flex flex-col gap-4">
                <TabsList
                    :aria-label="t('upload.formatLabel')"
                    class="inline-flex self-start rounded-m bg-sf2 p-1"
                >
                    <TabsTrigger
                        v-for="tab in [
                            {
                                value: 'text',
                                label: t('upload.tabText'),
                                icon: 'notes',
                            },
                            {
                                value: 'pdf',
                                label: t('upload.tabPdf'),
                                icon: 'picture_as_pdf',
                            },
                        ]"
                        :key="tab.value"
                        :value="tab.value"
                        class="inline-flex min-h-11 items-center gap-2 rounded-s px-4 font-bold text-ink2 data-[state=active]:bg-sf data-[state=active]:text-ink data-[state=active]:shadow-sh1"
                    >
                        <Icon :name="tab.icon" :size="20" />{{ tab.label }}
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="text">
                    <TextArea
                        v-model="form.text"
                        :label="t('upload.textLabel')"
                        :placeholder="t('upload.textPlaceholder')"
                        :rows="9"
                        :error="clientErrors.text ?? form.errors.text"
                        :hint="wordsLabel"
                    />
                    <p
                        v-if="missingWords > 0 && !clientErrors.text"
                        role="status"
                        class="mt-2 mb-0 flex items-start gap-2 rounded-s bg-infot p-3 text-sm text-ink"
                    >
                        <Icon name="info" :size="20" class="text-infod" />
                        {{
                            t('upload.wordsMissing', {
                                n: missingWords,
                                min: limits.min_words,
                            })
                        }}
                    </p>
                </TabsContent>

                <TabsContent value="pdf" class="flex flex-col gap-2">
                    <div
                        v-if="form.pdf"
                        class="flex items-center gap-3 rounded-m border-[1.5px] border-line bg-sf p-3"
                    >
                        <span
                            class="grid size-11 shrink-0 place-items-center rounded-s bg-prit text-prid"
                            aria-hidden="true"
                        >
                            <Icon name="picture_as_pdf" />
                        </span>
                        <span class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate font-bold">{{
                                form.pdf.name
                            }}</span>
                            <span class="text-sm text-ink2">{{
                                t('upload.pdfSize', { size: pdfMegabytes })
                            }}</span>
                        </span>
                        <button
                            type="button"
                            class="grid size-11 place-items-center rounded-s text-ink2 hover:bg-sf2"
                            :aria-label="t('upload.pdfRemove')"
                            @click="removePdf"
                        >
                            <Icon name="close" />
                        </button>
                    </div>
                    <label
                        class="flex cursor-pointer flex-col items-center gap-1 rounded-m border-2 border-dashed border-line bg-sf2 p-6 text-center focus-within:border-prid"
                        :class="{ 'sr-only': form.pdf }"
                    >
                        <Icon
                            name="picture_as_pdf"
                            :size="32"
                            class="text-prid"
                        />
                        <span class="font-extrabold text-prid">{{
                            t('upload.pdfPick')
                        }}</span>
                        <span class="text-sm text-ink2">{{
                            t('upload.pdfHint', {
                                mb: limits.max_megabytes,
                                pages: limits.max_pages,
                            })
                        }}</span>
                        <input
                            ref="pdfInput"
                            type="file"
                            accept="application/pdf,.pdf"
                            class="sr-only"
                            :aria-invalid="
                                clientErrors.pdf || form.errors.pdf
                                    ? 'true'
                                    : undefined
                            "
                            aria-describedby="upload-pdf-message"
                            @change="pickPdf"
                        />
                    </label>
                    <p
                        v-if="clientErrors.pdf || form.errors.pdf"
                        id="upload-pdf-message"
                        role="alert"
                        class="m-0 flex items-start gap-1.5 text-[13.5px] font-semibold text-errd"
                    >
                        <Icon name="error" :size="17" />{{
                            clientErrors.pdf ?? form.errors.pdf
                        }}
                    </p>
                    <p
                        v-else-if="form.pdf"
                        id="upload-pdf-message"
                        role="status"
                        class="m-0 flex items-center gap-1.5 text-[13.5px] font-semibold text-okd"
                    >
                        <Icon name="check_circle" :size="17" />{{
                            t('upload.pdfReady')
                        }}
                    </p>
                </TabsContent>
            </TabsRoot>

            <div class="grid gap-4 md:grid-cols-[1.4fr_1fr_1fr]">
                <TextField
                    v-model="form.title"
                    :label="t('upload.titleLabel')"
                    :placeholder="t('upload.titlePlaceholder')"
                    maxlength="120"
                    :error="form.errors.title"
                />
                <TextField
                    v-model="form.lesson_date"
                    type="date"
                    :label="t('upload.dateLabel')"
                    :error="form.errors.lesson_date"
                />
                <Select
                    v-model="language"
                    :label="t('upload.languageLabel')"
                    :options="languageOptions"
                />
            </div>

            <div>
                <Checkbox
                    v-model="form.rights"
                    :aria-invalid="form.errors.rights ? 'true' : undefined"
                    :aria-describedby="
                        form.errors.rights ? 'upload-rights-error' : undefined
                    "
                >
                    {{ t('upload.rightsStart') }}
                    <Link href="/termos" class="font-bold text-prid">{{
                        t('upload.rightsTerms')
                    }}</Link
                    >{{ t('upload.rightsEnd') }}
                </Checkbox>
                <p
                    v-if="form.errors.rights"
                    id="upload-rights-error"
                    class="mt-1.5 mb-0 flex items-center gap-1.5 text-[13.5px] font-semibold text-errd"
                >
                    <Icon name="error" :size="17" />{{ form.errors.rights }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <Button
                    type="submit"
                    icon="auto_awesome"
                    :loading="form.processing"
                    >{{ t('upload.submit') }}</Button
                >
                <p class="m-0 text-sm text-ink2">
                    {{ t('upload.submitHint') }}
                </p>
            </div>
        </form>
    </Card>
</template>
