<script setup lang="ts">
import { Head, usePoll } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Card from '@/components/fixa/Card.vue';
import OriginTag from '@/components/fixa/OriginTag.vue';
import GenerationProgress from '@/components/lessons/GenerationProgress.vue';
import { isProcessing, shortDate } from '@/lib/lessons';
import type { LessonDetail, LessonReview } from '@/types/lessons';

/**
 * Tela da aula: progresso da geração e, depois, a revisão (J2-F3, J2-F4).
 */
const props = defineProps<{
    lesson: LessonDetail;
    review: LessonReview | null;
}>();

const processing = computed(() => isProcessing(props.lesson.status));

// Consulta só o estado da aula enquanto a fila trabalha (J2-B11).
const { start, stop } = usePoll(
    2000,
    { only: ['lesson', 'review'] },
    { autoStart: false },
);

watch(
    processing,
    (now) => {
        if (now) {
            start();
        } else {
            stop();
        }
    },
    { immediate: true },
);
</script>

<template>
    <Head :title="lesson.title" />

    <OriginTag
        :lesson-date="shortDate(lesson.lesson_date)"
        class="self-start"
    />
    <h1 class="m-0 type-display-l">{{ lesson.title }}</h1>

    <GenerationProgress v-if="processing" :status="lesson.status" />

    <Card v-else-if="review">
        <p class="m-0 type-body">{{ review.summary }}</p>
    </Card>
</template>
