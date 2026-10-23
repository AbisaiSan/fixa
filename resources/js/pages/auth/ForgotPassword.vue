<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import Banner from '@/components/fixa/Banner.vue';
import Button from '@/components/fixa/Button.vue';
import Icon from '@/components/fixa/Icon.vue';
import TextField from '@/components/fixa/TextField.vue';
import { t } from '@/i18n';
import { focusFirstError } from '@/lib/forms';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: t('auth.recoverTitle'),
        description: t('auth.recoverBody'),
    },
});

defineProps<{ status?: string }>();
</script>

<template>
    <Banner
        v-if="status"
        tone="info"
        icon="forward_to_inbox"
        :title="t('auth.recoverSentTitle')"
    >
        {{ t('auth.recoverSentBody') }}
    </Banner>

    <Form
        v-bind="email.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4.5"
        @error="focusFirstError()"
    >
        <TextField
            :label="t('auth.email')"
            name="email"
            type="email"
            required
            autofocus
            autocomplete="email"
            :error="errors.email"
        />
        <Button type="submit" icon="send" :loading="processing" block>{{
            t('auth.recoverAction')
        }}</Button>
    </Form>

    <Link
        :href="login()"
        class="flex min-h-11 items-center gap-1.5 self-start font-bold text-prid"
    >
        <Icon name="arrow_back" :size="20" />{{ t('auth.backToLogin') }}
    </Link>
</template>
