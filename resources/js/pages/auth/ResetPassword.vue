<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import Button from '@/components/fixa/Button.vue';
import PasswordField from '@/components/fixa/PasswordField.vue';
import TextField from '@/components/fixa/TextField.vue';
import { t } from '@/i18n';
import { focusFirstError } from '@/lib/forms';
import { update } from '@/routes/password';

defineOptions({
    layout: { title: t('auth.resetTitle'), description: t('auth.resetBody') },
});

defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();
</script>

<template>
    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4.5"
        @error="focusFirstError()"
    >
        <TextField
            :label="t('auth.email')"
            name="email"
            type="email"
            :model-value="email"
            readonly
            autocomplete="email"
            :error="errors.email"
        />
        <PasswordField
            :label="t('auth.newPassword')"
            name="password"
            required
            autofocus
            autocomplete="new-password"
            :passwordrules="passwordRules"
            :hint="t('auth.passwordHint')"
            :error="errors.password"
        />
        <PasswordField
            :label="t('auth.passwordConfirmation')"
            name="password_confirmation"
            required
            autocomplete="new-password"
            :error="errors.password_confirmation"
        />
        <Button type="submit" :loading="processing" block>{{
            t('auth.resetAction')
        }}</Button>
    </Form>
</template>
