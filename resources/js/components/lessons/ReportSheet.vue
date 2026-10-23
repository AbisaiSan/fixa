<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import Button from '@/components/fixa/Button.vue';
import Sheet from '@/components/fixa/Sheet.vue';
import TextArea from '@/components/fixa/TextArea.vue';
import { t } from '@/i18n';

/**
 * "Reportar erro" num item gerado (MAT-05): motivo e detalhes opcionais.
 * Serve palavras e cartões; a revisão da aula tem a própria folha.
 */
const props = defineProps<{ url: string }>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({ reason: 'wrong_translation', details: '' });

const reasons = [
    { value: 'wrong_translation', label: t('review.reasonWrongTranslation') },
    {
        value: 'confusing_explanation',
        label: t('review.reasonConfusingExplanation'),
    },
    { value: 'unnatural_example', label: t('review.reasonUnnaturalExample') },
    { value: 'other', label: t('review.reasonOther') },
];

watch(open, (now) => {
    if (now) {
        form.reset();
        form.clearErrors();
    }
});

function send(): void {
    form.post(props.url, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Sheet
        v-model:open="open"
        :title="t('review.reportTitle')"
        :description="t('review.reportBody')"
    >
        <form class="flex flex-col gap-4" novalidate @submit.prevent="send">
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
                        v-model="form.reason"
                        type="radio"
                        name="report-sheet-reason"
                        :value="reason.value"
                        class="m-0 size-5 accent-prid"
                    />
                    {{ reason.label }}
                </label>
            </fieldset>
            <TextArea
                v-model="form.details"
                :label="t('review.reportDetails')"
                :placeholder="t('review.reportDetailsPlaceholder')"
                :rows="3"
                maxlength="500"
                :error="form.errors.details"
            />
            <Button type="submit" block :loading="form.processing">{{
                t('review.send')
            }}</Button>
            <Button variant="ghost" block @click="open = false">{{
                t('review.cancel')
            }}</Button>
        </form>
    </Sheet>
</template>
