<script setup lang="ts">
import Button from '@/components/fixa/Button.vue';
import Icon from '@/components/fixa/Icon.vue';
import { t } from '@/i18n';

/**
 * Item reportado (MAT-05): fora da rotina e "em análise", com "Desfazer".
 */
defineProps<{ busy?: boolean }>();

defineEmits<{ undo: [] }>();
</script>

<template>
    <div
        role="status"
        class="flex flex-wrap items-center gap-3 rounded-m bg-infot px-3.5 py-3"
    >
        <Icon name="flag" class="text-infod" />
        <div class="min-w-0 flex-1">
            <p class="m-0 font-bold">{{ t('review.reportedTitle') }}</p>
            <p class="m-0 text-sm text-ink2">{{ t('review.reportedBody') }}</p>
        </div>
        <Button variant="ghost" :loading="busy" @click="$emit('undo')">{{
            t('review.undo')
        }}</Button>
    </div>
</template>
