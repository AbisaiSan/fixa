<script setup lang="ts">
import { t } from '@/i18n';
import Icon from './Icon.vue';

/**
 * "Reportar erro" em item gerado (J4-F8, MAT-05). Reportado, mostra "Em
 * análise" com "Desfazer". O rótulo pode citar o item para leitores de tela.
 */
withDefaults(defineProps<{ reported?: boolean; label?: string }>(), {
    reported: false,
    label: undefined,
});

defineEmits<{ report: []; undo: [] }>();
</script>

<template>
    <span
        v-if="reported"
        role="status"
        class="inline-flex items-center gap-2 text-sm"
    >
        <span
            class="inline-flex items-center gap-1 rounded-full bg-infot px-2.5 py-1 font-bold text-infod"
        >
            <Icon name="flag" :size="16" />{{ t('report.underReview') }}
        </span>
        <button
            type="button"
            class="rounded-s px-2 py-1 font-bold text-prid hover:bg-prit"
            @click="$emit('undo')"
        >
            {{ t('report.undo') }}
        </button>
    </span>
    <button
        v-else
        type="button"
        class="inline-flex items-center gap-1.5 rounded-s px-2 py-1.5 text-sm font-bold text-ink2 hover:bg-sf2"
        :aria-label="label"
        @click="$emit('report')"
    >
        <Icon name="flag" :size="18" />{{ t('report.action') }}
    </button>
</template>
