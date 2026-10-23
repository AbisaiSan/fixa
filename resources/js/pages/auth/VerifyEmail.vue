<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import Banner from '@/components/fixa/Banner.vue';
import Button from '@/components/fixa/Button.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import { t } from '@/i18n';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: { title: t('auth.verifyTitle') },
});

defineProps<{ status?: string }>();
</script>

<template>
    <EmptyState icon="mark_email_unread" :title="t('auth.verifyTitle')">
        {{ t('auth.verifyBody') }}
    </EmptyState>

    <Banner
        v-if="status === 'verification-link-sent'"
        tone="info"
        icon="forward_to_inbox"
        :title="t('auth.verifySent')"
    />

    <Form v-bind="send.form()" v-slot="{ processing }">
        <Button
            type="submit"
            variant="secondary"
            icon="refresh"
            :loading="processing"
            block
            >{{ t('auth.verifyResend') }}</Button
        >
    </Form>

    <Link
        :href="logout()"
        method="post"
        as="button"
        class="min-h-11 self-center font-bold text-prid"
    >
        {{ t('auth.logout') }}
    </Link>
</template>
