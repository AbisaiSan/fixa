<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import Icon from '@/components/fixa/Icon.vue';
import PageState from '@/components/fixa/PageState.vue';
import type { PageStatus } from '@/components/fixa/PageState.vue';
import { t } from '@/i18n';
import { isProcessing, shortDate, sourceLabel } from '@/lib/lessons';
import type { LessonListItem } from '@/types/lessons';

/**
 * Lista de aulas (J2-F1, protótipo tela 5): origem, data, status e resumo.
 */
defineProps<{ status: PageStatus; lessons: LessonListItem[] }>();

function statusOf(lesson: LessonListItem): {
    icon: string;
    label: string;
    classes: string;
} {
    if (isProcessing(lesson.status)) {
        return {
            icon: 'autorenew',
            label: t('lessons.statusProcessing'),
            classes: 'bg-prit text-prid',
        };
    }

    return lesson.status === 'failed'
        ? {
              icon: 'error',
              label: t('lessons.statusFailed'),
              classes: 'bg-errt text-errd',
          }
        : {
              icon: 'done_all',
              label: t('lessons.statusReady'),
              classes: 'bg-okt text-okd',
          };
}

function conceptsLine(lesson: LessonListItem): string | null {
    if (lesson.status !== 'ready' || lesson.concepts === null) {
        return null;
    }

    return lesson.concepts === 1
        ? t('lessons.conceptsOne')
        : t('lessons.concepts', { n: lesson.concepts });
}
</script>

<template>
    <Head :title="t('nav.lessons')" />

    <h1 class="m-0 type-display-l">{{ t('nav.lessons') }}</h1>

    <PageState :status="status">
        <template #empty>
            <Card>
                <EmptyState icon="menu_book" :title="t('empty.lessonsTitle')">
                    {{ t('empty.lessonsBody') }}
                    <template #action>
                        <Button
                            href="/aulas/enviar"
                            variant="secondary"
                            class="mt-2"
                            >{{ t('empty.uploadAction') }}</Button
                        >
                    </template>
                </EmptyState>
            </Card>
        </template>

        <p class="m-0 text-ink2">
            {{
                lessons.length === 1
                    ? t('lessons.countOne')
                    : t('lessons.count', { n: lessons.length })
            }}
        </p>

        <ul
            class="m-0 flex list-none flex-col gap-3 p-0"
            :aria-label="t('lessons.listLabel')"
        >
            <li v-for="lesson in lessons" :key="lesson.ulid">
                <Link
                    :href="`/aulas/${lesson.ulid}`"
                    class="flex items-center gap-4 rounded-l border-[1.5px] border-line bg-sf p-4 text-ink no-underline shadow-sh1 transition duration-(--dur-fast) hover:border-ink3"
                >
                    <span
                        class="grid size-12 shrink-0 place-items-center rounded-m bg-prit text-prid"
                        aria-hidden="true"
                    >
                        <Icon
                            :name="
                                lesson.source === 'pdf'
                                    ? 'picture_as_pdf'
                                    : 'notes'
                            "
                        />
                    </span>
                    <span class="flex min-w-0 flex-1 flex-col gap-1">
                        <span class="font-extrabold">{{ lesson.title }}</span>
                        <span class="text-sm text-ink2">{{
                            t('lessons.dateLine', {
                                date: shortDate(lesson.lesson_date),
                                source: sourceLabel(
                                    lesson.source,
                                    lesson.pages,
                                ),
                            })
                        }}</span>
                        <span
                            v-if="conceptsLine(lesson)"
                            class="text-sm text-ink2"
                            >{{ conceptsLine(lesson) }}</span
                        >
                        <span
                            class="mt-1 inline-flex items-center gap-1 self-start rounded-full px-2.5 py-0.5 text-[12.5px] font-bold"
                            :class="statusOf(lesson).classes"
                        >
                            <Icon :name="statusOf(lesson).icon" :size="15" />{{
                                statusOf(lesson).label
                            }}
                        </span>
                    </span>
                    <Icon name="chevron_right" class="text-ink2" />
                </Link>
            </li>
        </ul>
    </PageState>
</template>
