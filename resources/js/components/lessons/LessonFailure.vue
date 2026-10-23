<script setup lang="ts">
import { computed } from 'vue';
import Banner from '@/components/fixa/Banner.vue';
import Button from '@/components/fixa/Button.vue';
import { t } from '@/i18n';
import type { StringKey } from '@/i18n';
import { ptBR } from '@/i18n/pt-BR';

/**
 * Falha da aula (J2-F5): mensagem amigável para cada código e o próximo
 * passo, "tentar de novo" quando outra tentativa pode resolver ou "enviar de
 * novo" quando o material precisa mudar.
 */
const props = defineProps<{
    reason: string | null;
    canRetry: boolean;
    retrying?: boolean;
}>();

defineEmits<{ retry: [] }>();

const RETRYABLE = [
    'ai_invalid_output',
    'ai_unavailable',
    'ai_budget_reached',
    'processing_failed',
];

const code = computed(() =>
    props.reason && props.reason in ptBR.failure
        ? props.reason
        : 'processing_failed',
);
const retryable = computed(() => RETRYABLE.includes(code.value));
</script>

<template>
    <div class="flex flex-col items-start gap-3">
        <Banner
            tone="error"
            icon="error"
            :title="t(`failure.${code}.title` as StringKey)"
            >{{ t(`failure.${code}.body` as StringKey) }}</Banner
        >
        <p v-if="retryable && !canRetry" class="m-0 text-sm text-ink2">
            {{ t('failure.noRetries') }}
        </p>
        <div class="flex flex-wrap gap-2">
            <Button
                v-if="retryable && canRetry"
                icon="refresh"
                :loading="retrying"
                @click="$emit('retry')"
                >{{ t('failure.retry') }}</Button
            >
            <Button
                href="/aulas/enviar"
                :variant="retryable && canRetry ? 'secondary' : 'primary'"
                icon="upload"
                >{{ t('failure.resend') }}</Button
            >
        </div>
    </div>
</template>
