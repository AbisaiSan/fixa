<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import Button from '@/components/fixa/Button.vue';
import Checkbox from '@/components/fixa/Checkbox.vue';
import Icon from '@/components/fixa/Icon.vue';
import PasswordField from '@/components/fixa/PasswordField.vue';
import TextField from '@/components/fixa/TextField.vue';
import { t } from '@/i18n';
import { focusFirstError } from '@/lib/forms';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{ passwordRules: string }>();

defineOptions({
    layout: {
        title: t('auth.registerTitle'),
        description: t('auth.registerBody'),
    },
});
</script>

<template>
    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4.5"
        @error="focusFirstError()"
    >
        <TextField
            :label="t('auth.name')"
            name="name"
            required
            autofocus
            autocomplete="name"
            :error="errors.name"
        />
        <TextField
            :label="t('auth.email')"
            name="email"
            type="email"
            required
            autocomplete="email"
            :error="errors.email"
        />
        <PasswordField
            :label="t('auth.password')"
            name="password"
            required
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

        <div>
            <Checkbox
                name="terms"
                value="1"
                required
                :aria-invalid="errors.terms ? 'true' : undefined"
                :aria-describedby="errors.terms ? 'terms-error' : undefined"
            >
                {{ t('legal.acceptLabelStart') }}
                <a href="/termos" target="_blank" class="font-bold text-prid">{{
                    t('legal.terms')
                }}</a>
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
                class="m-0 flex items-center gap-1.5 text-[13.5px] font-semibold text-errd"
            >
                <Icon name="error" :size="17" />{{ errors.terms }}
            </p>
        </div>

        <Button type="submit" :loading="processing" block>{{
            t('auth.createAccount')
        }}</Button>

        <p class="m-0 text-center text-sm text-ink2">
            {{ t('auth.haveAccount') }}
            <Link :href="login()" class="font-bold text-prid">{{
                t('auth.loginAction')
            }}</Link>
        </p>
    </Form>
</template>
