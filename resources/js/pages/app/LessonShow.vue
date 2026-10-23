<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Card from '@/components/fixa/Card.vue';
import OriginTag from '@/components/fixa/OriginTag.vue';
import Skeleton from '@/components/fixa/Skeleton.vue';
import { t } from '@/i18n';

/**
 * Tela da aula. A revisão completa chega na J2-F4; por ora mostra o estado.
 */
defineProps<{
    lesson: {
        ulid: string;
        title: string;
        lesson_date: string;
        source: string;
        status: string;
        failure_reason: string | null;
    };
}>();
</script>

<template>
    <Head :title="lesson.title" />

    <OriginTag
        :lesson-date="
            lesson.lesson_date.split('-').reverse().slice(0, 2).join('/')
        "
        class="self-start"
    />
    <h1 class="m-0 type-display-l">{{ lesson.title }}</h1>

    <Card>
        <Skeleton :label="t('state.loading')" :lines="4" />
    </Card>
</template>
