<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Button from '@/components/fixa/Button.vue';
import TextField from '@/components/fixa/TextField.vue';
import { t } from '@/i18n';
import { focusFirstError } from '@/lib/forms';
import { store } from '@/routes/two-factor/login';

defineOptions({
    layout: { title: t('auth.twoFactorTitle') },
});

const useRecovery = ref(false);
const description = computed(() =>
    useRecovery.value ? t('auth.recoveryBody') : t('auth.twoFactorBody'),
);
</script>

<template>
    <p class="m-0 -mt-2 text-ink2">{{ description }}</p>

    <Form
        v-bind="store.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4.5"
        @error="focusFirstError()"
    >
        <TextField
            v-if="!useRecovery"
            :key="'code'"
            :label="t('auth.twoFactorCode')"
            name="code"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="6"
            required
            autofocus
            :error="errors.code"
        />
        <TextField
            v-else
            :key="'recovery'"
            :label="t('auth.recoveryCode')"
            name="recovery_code"
            autocomplete="one-time-code"
            required
            autofocus
            :error="errors.recovery_code"
        />
        <Button type="submit" icon="shield_lock" :loading="processing" block>{{
            t('auth.verifyAction')
        }}</Button>
    </Form>

    <Button
        variant="ghost"
        class="self-center"
        @click="useRecovery = !useRecovery"
    >
        {{ useRecovery ? t('auth.useApp') : t('auth.useRecovery') }}
    </Button>
</template>
