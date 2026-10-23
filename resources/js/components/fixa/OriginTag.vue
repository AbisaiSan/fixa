<script setup lang="ts">
import { t } from '@/i18n';
import Icon from './Icon.vue';

/**
 * De onde veio o item: de uma aula (data já formatada, ex.: "21/10") ou adicionado pelo aluno.
 */
defineProps<{ lessonDate?: string }>();
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-xs bg-sf2 px-2.25 py-1 text-[12.5px] font-bold text-ink2"
    >
        <template v-if="lessonDate">
            <Icon name="menu_book" :size="15" />{{
                t('origin.lesson', { date: lessonDate })
            }}
        </template>
        <template v-else>
            <Icon name="person" :size="15" />{{ t('origin.mine') }}
        </template>
    </span>
</template>
