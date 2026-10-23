<script setup lang="ts">
import { t } from '@/i18n';
import Banner from './Banner.vue';
import Button from './Button.vue';
import Skeleton from './Skeleton.vue';

export type PageStatus = 'ready' | 'empty' | 'loading' | 'error';

/**
 * Contrato de estados das telas de conteúdo (J1-F5): toda tela recebe
 * status ready, empty, loading ou error e mostra o slot correspondente.
 * Os estados loading e error têm um padrão que a tela pode substituir.
 */
defineProps<{ status: PageStatus }>();

defineEmits<{ retry: [] }>();
</script>

<template>
    <slot v-if="status === 'ready'" />
    <slot v-else-if="status === 'empty'" name="empty" />
    <slot v-else-if="status === 'loading'" name="loading">
        <Skeleton :label="t('state.loading')" :lines="4" />
    </slot>
    <slot v-else name="error">
        <div class="flex flex-col items-start gap-3">
            <Banner tone="error" icon="error" :title="t('state.errorTitle')">{{
                t('state.errorBody')
            }}</Banner>
            <Button
                variant="secondary"
                icon="refresh"
                @click="$emit('retry')"
                >{{ t('state.retry') }}</Button
            >
        </div>
    </slot>
</template>
