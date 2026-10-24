<script setup lang="ts">
import { onMounted, ref } from 'vue';
import Button from '@/components/fixa/Button.vue';
import { t } from '@/i18n';
import { getJson } from '@/lib/http';
import { blockShort } from '@/lib/plan';
import type { NextBlockSummary, PlanBlock } from '@/types/plan';

/**
 * Ações do fim de uma sessão (J5-F2, fluxo B): o próximo bloco do plano de
 * hoje a 1 clique e a volta para Hoje. Sem próximo bloco, Hoje vira o
 * caminho principal. Avisa quem está em volta quando a meta foi cumprida.
 */
const props = withDefaults(
    defineProps<{
        current: string;
        fallbackHref?: string;
        fallbackLabel?: string;
    }>(),
    { fallbackHref: '/hoje', fallbackLabel: undefined },
);

const emit = defineEmits<{ loaded: [summary: NextBlockSummary] }>();

const next = ref<PlanBlock | null>(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const summary = await getJson<NextBlockSummary>(
            `/hoje/proximo?atual=${encodeURIComponent(props.current)}`,
        );
        next.value = summary.next;
        emit('loaded', summary);
    } catch {
        // Sem conexão: ficam os caminhos de sempre.
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="flex w-full flex-col gap-2">
        <Button
            v-if="next?.href"
            block
            :href="next.href"
            icon="arrow_forward"
            >{{ t('nextBlock.label', { block: blockShort(next) }) }}</Button
        >
        <Button
            v-else
            block
            :href="fallbackHref"
            icon="arrow_forward"
            :loading="loading"
            >{{ fallbackLabel ?? t('nextBlock.backToday') }}</Button
        >
        <Button v-if="next?.href" variant="secondary" block href="/hoje">{{
            t('nextBlock.backToday')
        }}</Button>
    </div>
</template>
