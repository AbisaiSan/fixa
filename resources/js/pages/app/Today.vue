<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import Icon from '@/components/fixa/Icon.vue';
import OriginTag from '@/components/fixa/OriginTag.vue';
import PageState from '@/components/fixa/PageState.vue';
import type { PageStatus } from '@/components/fixa/PageState.vue';
import { t } from '@/i18n';
import { isProcessing, shortDate } from '@/lib/lessons';
import type { LessonStatus } from '@/types/lessons';

const props = defineProps<{
    status: PageStatus;
    latestLesson?: {
        ulid: string;
        title: string;
        lesson_date: string;
        status: LessonStatus;
    } | null;
}>();

// Fluxo A (J2-F6): da aula enviada até a revisão pronta.
const latest = computed(() => {
    const lesson = props.latestLesson;

    if (!lesson) {
        return null;
    }

    if (isProcessing(lesson.status)) {
        return {
            body: t('today.latestProcessing'),
            action: t('today.followGeneration'),
            icon: 'autorenew',
        };
    }

    return lesson.status === 'failed'
        ? {
              body: t('today.latestFailed'),
              action: t('today.seeWhatHappened'),
              icon: 'error',
          }
        : {
              body: t('today.latestReady'),
              action: t('today.openReview'),
              icon: 'menu_book',
          };
});

const page = usePage();

const greeting = computed(() => {
    const hour = new Date().getHours();
    const key =
        hour < 12
            ? 'greeting.morning'
            : hour < 18
              ? 'greeting.afternoon'
              : 'greeting.evening';
    const firstName = page.props.auth.user.name.split(' ')[0];

    return t(key, { name: firstName });
});

const steps = computed(() => [
    { n: 1, title: t('today.step1Title'), body: t('today.step1Body') },
    { n: 2, title: t('today.step2Title'), body: t('today.step2Body') },
    {
        n: 3,
        title: t('today.step3Title', { minutes: 10 }),
        body: t('today.step3Body'),
    },
]);
</script>

<template>
    <Head :title="t('nav.today')" />

    <h1 class="m-0 type-display-l">{{ greeting }}</h1>

    <PageState :status="status">
        <Card
            v-if="latestLesson && latest"
            tone="hero"
            as="section"
            aria-labelledby="today-latest-title"
            class="flex flex-col gap-3"
        >
            <p class="m-0 type-label text-ink2">
                {{ t('today.latestLabel') }}
            </p>
            <OriginTag
                :lesson-date="shortDate(latestLesson.lesson_date)"
                class="self-start"
            />
            <h2 id="today-latest-title" class="m-0 type-title">
                {{ latestLesson.title }}
            </h2>
            <p class="m-0 max-w-140 text-ink2 type-body">{{ latest.body }}</p>
            <div class="flex flex-wrap gap-2">
                <Button
                    :href="`/aulas/${latestLesson.ulid}`"
                    :icon="latest.icon"
                    >{{ latest.action }}</Button
                >
                <Button
                    href="/aulas/enviar"
                    variant="secondary"
                    icon="upload"
                    >{{ t('today.uploadAnother') }}</Button
                >
            </div>
        </Card>

        <template #empty>
            <Card
                tone="hero"
                as="section"
                aria-labelledby="today-empty-title"
                class="flex flex-col gap-4"
            >
                <span
                    class="grid size-12 place-items-center rounded-m bg-sf text-prid shadow-sh1"
                    aria-hidden="true"
                >
                    <Icon name="upload_file" :size="28" />
                </span>
                <h2 id="today-empty-title" class="m-0 type-title">
                    {{ t('today.emptyTitle') }}
                </h2>
                <p class="m-0 max-w-140 text-ink2 type-body">
                    {{ t('today.emptyBody') }}
                </p>
                <Button href="/aulas/enviar" icon="upload" class="self-start">{{
                    t('today.emptyAction')
                }}</Button>
            </Card>

            <section aria-labelledby="today-steps" class="flex flex-col gap-3">
                <h2 id="today-steps" class="m-0 type-label text-ink2">
                    {{ t('today.stepsLabel') }}
                </h2>
                <ol class="m-0 grid list-none gap-3 p-0 md:grid-cols-3">
                    <li
                        v-for="step in steps"
                        :key="step.n"
                        class="flex gap-3 rounded-l border border-line bg-sf p-4"
                    >
                        <span
                            class="grid size-9 shrink-0 place-items-center rounded-full bg-prit font-display text-lg text-prid"
                            aria-hidden="true"
                            >{{ step.n }}</span
                        >
                        <span>
                            <strong class="block">{{ step.title }}</strong>
                            <span class="text-sm text-ink2">{{
                                step.body
                            }}</span>
                        </span>
                    </li>
                </ol>
            </section>
        </template>
    </PageState>
</template>
