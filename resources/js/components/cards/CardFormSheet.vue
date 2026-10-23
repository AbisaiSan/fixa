<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import Button from '@/components/fixa/Button.vue';
import Sheet from '@/components/fixa/Sheet.vue';
import TextField from '@/components/fixa/TextField.vue';
import { useTargetLang } from '@/composables/useTargetLang';
import { t } from '@/i18n';

/**
 * Criar ou editar cartão (J3-F6, FLA-06): frente, verso e exemplo opcional.
 */
export type CardFields = {
    front: string;
    back: string;
    example: string | null;
};

const props = defineProps<{
    // Com cartão, edita; sem, cria (opcionalmente ligado a uma aula).
    card?: ({ ulid: string } & CardFields) | null;
    lesson?: string | null;
}>();

const emit = defineEmits<{ saved: [fields: CardFields] }>();

const open = defineModel<boolean>('open', { default: false });
const targetLang = useTargetLang();

const form = useForm({ front: '', back: '', example: '', lesson: '' });

watch(open, (now) => {
    if (!now) {
        return;
    }

    form.clearErrors();
    form.front = props.card?.front ?? '';
    form.back = props.card?.back ?? '';
    form.example = props.card?.example ?? '';
    form.lesson = props.card ? '' : (props.lesson ?? '');
});

function save(): void {
    const options = {
        preserveScroll: true,
        preserveState: true,
        only: ['flash'],
        onSuccess: () => {
            emit('saved', {
                front: form.front.trim(),
                back: form.back.trim(),
                example: form.example.trim() || null,
            });
            open.value = false;
        },
    };

    if (props.card) {
        form.transform(({ lesson: _lesson, ...fields }) => fields).patch(
            `/cartoes/${props.card.ulid}`,
            options,
        );
    } else {
        form.transform((fields) => ({
            ...fields,
            lesson: fields.lesson || null,
        })).post('/cartoes', options);
    }
}
</script>

<template>
    <Sheet
        v-model:open="open"
        :title="card ? t('cards.editTitle') : t('cards.newTitle')"
    >
        <form class="flex flex-col gap-4" novalidate @submit.prevent="save">
            <TextField
                v-model="form.front"
                :label="t('cards.front')"
                :lang="targetLang.code"
                maxlength="200"
                :error="form.errors.front"
            />
            <TextField
                v-model="form.back"
                :label="t('cards.back')"
                :placeholder="t('cards.backPlaceholder')"
                maxlength="300"
                :error="form.errors.back"
            />
            <TextField
                v-model="form.example"
                :label="t('cards.example')"
                :lang="targetLang.code"
                maxlength="300"
                :error="form.errors.example"
            />
            <Button type="submit" block :loading="form.processing">{{
                t('cards.save')
            }}</Button>
            <Button variant="ghost" block @click="open = false">{{
                t('cards.cancel')
            }}</Button>
        </form>
    </Sheet>
</template>
