<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import Button from '@/components/fixa/Button.vue';
import PasswordField from '@/components/fixa/PasswordField.vue';
import { t } from '@/i18n';
import { focusFirstError } from '@/lib/forms';
import { store } from '@/routes/password/confirm';

defineOptions({
    layout: {
        title: t('auth.confirmTitle'),
        description: t('auth.confirmBody'),
    },
});
</script>

<template>
    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4.5"
        @error="focusFirstError()"
    >
        <PasswordField
            :label="t('auth.password')"
            name="password"
            required
            autofocus
            :error="errors.password"
        />
        <Button type="submit" icon="lock_open" :loading="processing" block>{{
            t('auth.confirmAction')
        }}</Button>
    </Form>
</template>
