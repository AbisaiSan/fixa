<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import Banner from '@/components/fixa/Banner.vue';
import Button from '@/components/fixa/Button.vue';
import Checkbox from '@/components/fixa/Checkbox.vue';
import PasswordField from '@/components/fixa/PasswordField.vue';
import TextField from '@/components/fixa/TextField.vue';
import { t } from '@/i18n';
import { focusFirstError } from '@/lib/forms';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: { title: t('auth.loginTitle'), description: t('auth.loginBody') },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Banner v-if="status" tone="info" icon="check_circle" :title="status" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4.5"
        @error="focusFirstError()"
    >
        <Banner
            v-if="errors.email"
            tone="error"
            icon="error"
            :title="errors.email"
        >
            {{ t('auth.loginErrorBody') }}
        </Banner>

        <TextField
            :label="t('auth.email')"
            name="email"
            type="email"
            required
            autofocus
            autocomplete="email"
            :aria-invalid="errors.email ? 'true' : undefined"
        />

        <div class="flex flex-col gap-2">
            <PasswordField
                :label="t('auth.password')"
                name="password"
                required
                :error="errors.password"
            />
            <Link
                v-if="canResetPassword"
                :href="request()"
                class="min-h-11 self-end py-2.5 text-sm font-bold text-prid"
                >{{ t('auth.forgot') }}</Link
            >
        </div>

        <Checkbox name="remember" value="1">{{ t('auth.remember') }}</Checkbox>

        <Button type="submit" icon="login" :loading="processing" block>{{
            t('auth.loginAction')
        }}</Button>

        <p class="m-0 text-center text-sm text-ink2">
            {{ t('auth.newHere') }}
            <Link :href="register()" class="font-bold text-prid">{{
                t('auth.createAccount')
            }}</Link>
        </p>
    </Form>
</template>
