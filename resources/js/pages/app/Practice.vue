<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/fixa/Button.vue';
import AiLimitBanner from '@/components/practice/AiLimitBanner.vue';
import Card from '@/components/fixa/Card.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import Icon from '@/components/fixa/Icon.vue';
import PageState from '@/components/fixa/PageState.vue';
import type { PageStatus } from '@/components/fixa/PageState.vue';
import { t } from '@/i18n';

/**
 * Praticar (J4-F1, protótipo aba Praticar): flashcards, escrita, leitura,
 * ponto fraco, caderno de erros e modo corrida, tudo das aulas do aluno.
 */
const props = defineProps<{
    status: PageStatus;
    flashcards?: { due: number; new: number };
    writing?: {
        ulid: string;
        lesson: string | null;
        items: number;
        completed: boolean;
    } | null;
    reading?: {
        ulid: string;
        title: string;
        words: number;
        completed: boolean;
    } | null;
    weakPoint?: { due: number; point: string | null };
    mistakes?: { open: number; priority: number };
    flash?: string | null;
}>();

const page = usePage();
const aiLimitReached = computed(() => page.props.aiLimit?.reached === true);

type Entry = {
    key: string;
    icon: string;
    title: string;
    meta: string;
    href?: string;
};

const flashcardsMeta = computed(() => {
    const due = props.flashcards?.due ?? 0;
    const fresh = props.flashcards?.new ?? 0;

    if (due + fresh === 0) {
        return t('practice.flashcardsDone');
    }

    return t('practice.flashcardsMeta', {
        due:
            due === 1
                ? t('flashcards.dueOne')
                : t('flashcards.dueMany', { n: due }),
        new:
            fresh === 1
                ? t('flashcards.newOne')
                : t('flashcards.newMany', { n: fresh }),
    });
});

const entries = computed<Entry[]>(() => {
    const writing = props.writing;
    const reading = props.reading;
    const weak = props.weakPoint;
    const mistakes = props.mistakes;

    return [
        {
            key: 'flashcards',
            icon: 'style',
            title: t('flashcards.title'),
            meta: flashcardsMeta.value,
            href: '/praticar/flashcards',
        },
        {
            key: 'writing',
            icon: 'edit_note',
            title: t('practice.writing'),
            meta: !writing
                ? t('practice.writingNone')
                : aiLimitReached.value
                  ? t('practice.writingLimit')
                  : writing.completed
                    ? t('practice.writingDone', {
                          lesson: writing.lesson ?? '',
                      })
                    : t('practice.writingMeta', {
                          lesson: writing.lesson ?? '',
                          n: writing.items,
                      }),
            href: writing ? `/praticar/escrita/${writing.ulid}` : undefined,
        },
        {
            key: 'reading',
            icon: 'chrome_reader_mode',
            title: t('practice.reading'),
            meta: reading
                ? t('practice.readingMeta', {
                      title: reading.title,
                      words: reading.words,
                      minutes: Math.max(1, Math.round(reading.words / 50)),
                  })
                : t('practice.readingNone'),
            href: reading ? `/praticar/leitura/${reading.ulid}` : undefined,
        },
        {
            key: 'weak',
            icon: 'adjust',
            title: t('practice.weakPoint'),
            meta:
                weak && weak.due > 0
                    ? t('practice.weakPointMeta', {
                          point: weak.point ?? '',
                          n: weak.due,
                      })
                    : t('practice.weakPointNone'),
            href: weak && weak.due > 0 ? '/praticar/ponto-fraco' : undefined,
        },
        {
            key: 'mistakes',
            icon: 'auto_stories',
            title: t('practice.mistakes'),
            meta:
                mistakes && mistakes.open > 0
                    ? t('practice.mistakesMeta', {
                          n: mistakes.open,
                          priority: mistakes.priority,
                      })
                    : t('practice.mistakesNone'),
            href: '/praticar/caderno-de-erros',
        },
        {
            key: 'rush',
            icon: 'bolt',
            title: t('practice.rush'),
            meta: t('practice.rushMeta'),
            href: '/praticar/flashcards?modo=corrida',
        },
    ];
});
</script>

<template>
    <Head :title="t('nav.practice')" />

    <h1 class="m-0 type-display-l">{{ t('nav.practice') }}</h1>

    <AiLimitBanner />

    <PageState :status="status">
        <template #empty>
            <Card>
                <EmptyState icon="edit_note" :title="t('empty.practiceTitle')">
                    {{ t('empty.practiceBody') }}
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

        <p
            v-if="flash === 'weak-point-empty'"
            role="status"
            class="m-0 rounded-m bg-okt px-4 py-3 font-bold text-okd"
        >
            {{ t('practice.weakPointNone') }}
        </p>
        <p class="m-0 text-ink2">{{ t('practice.intro') }}</p>
        <ul class="m-0 grid list-none grid-cols-1 gap-3 p-0 md:grid-cols-2">
            <li v-for="entry in entries" :key="entry.key">
                <component
                    :is="entry.href ? Link : 'div'"
                    :href="entry.href"
                    class="flex h-full items-center gap-4 rounded-l border-[1.5px] border-line bg-sf p-4 text-ink no-underline shadow-sh1"
                    :class="
                        entry.href
                            ? 'transition duration-(--dur-fast) hover:border-ink3'
                            : 'opacity-80'
                    "
                >
                    <span
                        class="grid size-12 shrink-0 place-items-center rounded-m bg-prit text-prid"
                        aria-hidden="true"
                    >
                        <Icon :name="entry.icon" />
                    </span>
                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span class="font-extrabold">{{ entry.title }}</span>
                        <span class="text-sm text-ink2">{{ entry.meta }}</span>
                    </span>
                    <Icon
                        v-if="entry.href"
                        name="chevron_right"
                        class="text-ink2"
                    />
                </component>
            </li>
            <li>
                <div
                    class="flex h-full items-center gap-4 rounded-l border-[1.5px] border-dashed border-line bg-sf2 p-4"
                >
                    <span
                        class="grid size-12 shrink-0 place-items-center rounded-m bg-sf text-ink2"
                        aria-hidden="true"
                    >
                        <Icon name="record_voice_over" />
                    </span>
                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span class="flex items-center gap-2 font-extrabold"
                            >{{ t('practice.voice')
                            }}<span
                                class="rounded-full bg-infot px-2 py-0.5 text-[11.5px] font-bold text-infod"
                                >{{ t('practice.phase2') }}</span
                            ></span
                        >
                        <span class="text-sm text-ink2">{{
                            t('practice.voiceMeta')
                        }}</span>
                    </span>
                </div>
            </li>
        </ul>
    </PageState>
</template>
