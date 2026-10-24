<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/fixa/Button.vue';
import AiLimitBanner from '@/components/practice/AiLimitBanner.vue';
import Banner from '@/components/fixa/Banner.vue';
import Card from '@/components/fixa/Card.vue';
import FreezeSlots from '@/components/fixa/FreezeSlots.vue';
import Icon from '@/components/fixa/Icon.vue';
import OriginTag from '@/components/fixa/OriginTag.vue';
import PageState from '@/components/fixa/PageState.vue';
import type { PageStatus } from '@/components/fixa/PageState.vue';
import ProgressRing from '@/components/fixa/ProgressRing.vue';
import StreakChip from '@/components/fixa/StreakChip.vue';
import { t } from '@/i18n';
import { isProcessing, shortDate } from '@/lib/lessons';
import {
    blockIcon,
    blockMeta,
    blockShort,
    blockTitle,
    weekdayName,
} from '@/lib/plan';
import type { LessonStatus } from '@/types/lessons';
import type { TodayPlan } from '@/types/plan';

const props = defineProps<{
    status: PageStatus;
    latestLesson?: {
        ulid: string;
        title: string;
        lesson_date: string;
        status: LessonStatus;
    } | null;
    plan?: TodayPlan | null;
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

// Plano do dia (J5-F1, PLA-06): o próximo bloco fica a 1 clique.
const nextBlock = computed(
    () =>
        props.plan?.blocks.find((block) => block.ulid === props.plan?.next) ??
        null,
);

const metaLine = computed(() => {
    const goal = props.plan?.goal;

    if (!goal) {
        return '';
    }

    if (goal.counted) {
        return t('today.metaDone');
    }

    const left = Math.max(0, goal.minutes - goal.done);

    if (goal.done === 0) {
        return t('today.metaStart', { goal: goal.minutes });
    }

    return left === 1
        ? t('today.metaLeftOne')
        : t('today.metaLeft', { n: left });
});

const metaSub = computed(() => {
    const plan = props.plan;

    if (!plan) {
        return '';
    }

    if (plan.goal.counted) {
        return plan.streak.current === 1
            ? t('today.metaStreakOne')
            : t('today.metaStreak', { n: plan.streak.current });
    }

    return nextBlock.value
        ? t('today.metaNext', { block: blockShort(nextBlock.value) })
        : '';
});

// Aula em processamento ou com falha continua em destaque (fluxo A).
const latestNeedsAttention = computed(
    () =>
        !!props.latestLesson &&
        (isProcessing(props.latestLesson.status) ||
            props.latestLesson.status === 'failed'),
);

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

    <Banner
        v-if="plan?.resume === 'welcome_back'"
        tone="info"
        icon="waving_hand"
        :title="t('resume.welcome_back')"
        role="status"
    >
        <span class="flex flex-col items-start gap-1">
            {{ t('resume.welcome_back_detail') }}
            <Link
                href="/plano"
                class="inline-flex min-h-11 items-center gap-1 font-bold text-infod"
                >{{ t('resume.see_plan') }}
                <Icon name="arrow_forward" :size="18" />
            </Link>
        </span>
    </Banner>
    <Banner
        v-else-if="plan?.resume === 'freeze_used'"
        tone="info"
        icon="ac_unit"
        :title="t('resume.freeze_used')"
        role="status"
    />

    <AiLimitBanner />

    <PageState :status="status">
        <div
            v-if="plan"
            class="grid gap-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]"
        >
            <div class="flex flex-col gap-4">
                <Card
                    as="section"
                    aria-labelledby="today-goal-title"
                    class="flex flex-col gap-4"
                >
                    <div class="flex items-center gap-5">
                        <ProgressRing
                            :percent="plan.goal.percent"
                            :label="
                                t('today.ringAria', {
                                    done: plan.goal.done,
                                    goal: plan.goal.minutes,
                                })
                            "
                        >
                            <Icon
                                v-if="plan.goal.counted"
                                name="check"
                                :size="44"
                                class="text-prid"
                            />
                            <span v-else class="flex flex-col">
                                <span
                                    class="font-display text-3xl leading-none"
                                    >{{ plan.goal.done }}</span
                                >
                                <span class="text-xs text-ink2">{{
                                    t('today.ringOf', {
                                        goal: plan.goal.minutes,
                                    })
                                }}</span>
                            </span>
                        </ProgressRing>
                        <div class="flex flex-col gap-1">
                            <p
                                id="today-goal-title"
                                class="m-0 type-label text-ink2"
                            >
                                {{ t('today.goalTitle') }}
                            </p>
                            <p class="m-0 type-title">{{ metaLine }}</p>
                            <p v-if="metaSub" class="m-0 text-sm text-ink2">
                                {{ metaSub }}
                            </p>
                        </div>
                    </div>
                    <Button
                        v-if="!plan.goal.counted && nextBlock?.href"
                        :href="nextBlock.href"
                        icon="play_arrow"
                        block
                        >{{
                            plan.goal.done === 0
                                ? t('today.start')
                                : t('today.continue')
                        }}</Button
                    >
                    <Button
                        v-else-if="plan.goal.counted"
                        href="/praticar"
                        variant="secondary"
                        block
                        >{{ t('today.practiceMore') }}</Button
                    >
                </Card>

                <div class="grid grid-cols-2 gap-3">
                    <StreakChip :days="plan.streak.current" />
                    <FreezeSlots
                        :count="plan.streak.freezes"
                        :max="plan.streak.max_freezes"
                    />
                </div>

                <Link
                    v-if="plan.rush.due > 0 && !plan.rush.done"
                    href="/praticar/flashcards?modo=corrida"
                    class="flex min-h-11 items-center gap-3 rounded-l border border-line bg-sf p-4 text-ink no-underline shadow-sh1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-prid"
                >
                    <span
                        class="grid size-10 shrink-0 place-items-center rounded-m bg-ambt text-ambd"
                        aria-hidden="true"
                    >
                        <Icon name="bolt" filled />
                    </span>
                    <span class="flex flex-1 flex-col">
                        <span class="font-extrabold">{{
                            t('today.rushTitle')
                        }}</span>
                        <span class="text-sm text-ink2">{{
                            t('today.rushBody')
                        }}</span>
                    </span>
                    <Icon name="chevron_right" class="text-ink2" />
                </Link>
                <p
                    v-else-if="plan.rush.done"
                    class="m-0 flex items-center gap-2 text-sm text-ink2"
                >
                    <Icon name="bolt" :size="18" />{{ t('today.rushDone') }}
                </p>
            </div>

            <section
                aria-labelledby="today-plan-title"
                class="flex flex-col gap-3"
            >
                <div class="flex items-center justify-between gap-2">
                    <h2 id="today-plan-title" class="m-0 type-subtitle">
                        {{ t('today.planTitle') }}
                    </h2>
                    <Link
                        href="/plano"
                        class="inline-flex min-h-11 items-center gap-1 px-2 font-bold text-prid no-underline"
                    >
                        <Icon name="calendar_month" :size="20" />{{
                            t('today.seeWeek')
                        }}
                    </Link>
                </div>

                <ol class="m-0 flex list-none flex-col gap-2.5 p-0">
                    <li v-for="(block, index) in plan.blocks" :key="block.ulid">
                        <component
                            :is="block.href ? Link : 'div'"
                            :href="block.href ?? undefined"
                            class="flex w-full items-center gap-3.5 rounded-l border border-line bg-sf p-4 text-left text-ink no-underline shadow-sh1"
                            :class="{
                                'hover:border-ink3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-prid':
                                    block.href,
                                'opacity-80': block.skipped,
                            }"
                        >
                            <span
                                class="grid size-12 shrink-0 place-items-center rounded-m"
                                :class="
                                    block.done
                                        ? 'bg-okt text-okd'
                                        : 'bg-prit text-prid'
                                "
                                aria-hidden="true"
                            >
                                <Icon
                                    :name="
                                        block.done
                                            ? 'check_circle'
                                            : blockIcon(block)
                                    "
                                    :filled="block.done"
                                />
                            </span>
                            <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                                <span class="text-xs font-bold text-ink2">{{
                                    t('today.block', { n: index + 1 })
                                }}</span>
                                <span class="font-extrabold">{{
                                    blockTitle(block)
                                }}</span>
                                <span class="text-sm text-ink2">{{
                                    blockMeta(block)
                                }}</span>
                                <span
                                    v-if="block.moved_from"
                                    class="text-sm text-infod"
                                    >{{
                                        t('today.movedFrom', {
                                            day: weekdayName(block.moved_from),
                                        })
                                    }}</span
                                >
                                <OriginTag
                                    v-else-if="block.lesson"
                                    :lesson-date="
                                        shortDate(block.lesson.lesson_date)
                                    "
                                    class="mt-1 self-start"
                                />
                                <span
                                    v-else
                                    class="mt-1 inline-flex items-center gap-1 self-start text-xs font-bold text-ink2"
                                >
                                    <Icon
                                        :name="
                                            block.kind === 'flashcards'
                                                ? 'schedule'
                                                : 'auto_stories'
                                        "
                                        :size="16"
                                    />{{
                                        block.kind === 'flashcards'
                                            ? t('blocks.tag_flashcards')
                                            : t('blocks.tag_weak_point')
                                    }}
                                </span>
                            </span>
                            <span
                                v-if="block.done"
                                class="text-sm font-bold text-okd"
                                >{{ t('today.blockDone') }}</span
                            >
                            <Icon
                                v-else-if="block.href"
                                name="chevron_right"
                                class="text-ink2"
                            />
                        </component>
                    </li>
                </ol>

                <Link
                    v-if="latestLesson && !latestNeedsAttention"
                    :href="`/aulas/${latestLesson.ulid}`"
                    class="flex min-h-11 items-center gap-3 rounded-l border border-line bg-sf2 p-4 text-ink no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-prid"
                >
                    <Icon name="menu_book" class="text-prid" />
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="text-xs font-bold text-ink2">{{
                            t('today.latestShort', {
                                date: shortDate(latestLesson.lesson_date),
                            })
                        }}</span>
                        <span class="truncate font-bold">{{
                            latestLesson.title
                        }}</span>
                    </span>
                    <span class="text-sm font-bold text-prid">{{
                        t('today.latestReview')
                    }}</span>
                </Link>
            </section>
        </div>

        <Card
            v-if="latestLesson && latest && (!plan || latestNeedsAttention)"
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
