<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import Checkbox from '@/components/fixa/Checkbox.vue';
import Icon from '@/components/fixa/Icon.vue';
import { t } from '@/i18n';

defineProps<{ versions: { terms: string; privacy: string } }>();
</script>

<template>
    <Head :title="t('legal.acceptTitle')" />

    <main
        class="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-6 px-5 py-10"
    >
        <h1 class="m-0 type-display-l">{{ t('legal.acceptTitle') }}</h1>
        <p class="m-0 text-ink2 type-body">{{ t('legal.acceptBody') }}</p>

        <Card>
            <Form
                action="/termos/aceitar"
                method="post"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-4"
            >
                <Checkbox
                    name="terms"
                    value="1"
                    :aria-invalid="errors.terms ? 'true' : undefined"
                    aria-describedby="terms-error"
                >
                    {{ t('legal.acceptLabelStart') }}
                    <a
                        href="/termos"
                        target="_blank"
                        class="font-bold text-prid"
                        >{{ t('legal.terms') }}</a
                    >
                    {{ t('legal.and') }}
                    <a
                        href="/privacidade"
                        target="_blank"
                        class="font-bold text-prid"
                        >{{ t('legal.privacy') }}</a
                    >.
                </Checkbox>
                <p
                    v-if="errors.terms"
                    id="terms-error"
                    role="alert"
                    class="m-0 flex items-center gap-1.5 text-[13.5px] font-semibold text-errd"
                >
                    <Icon name="error" :size="17" />{{ errors.terms }}
                </p>
                <Button type="submit" :loading="processing" block>{{
                    t('legal.acceptAction')
                }}</Button>
            </Form>
        </Card>
    </main>
</template>
