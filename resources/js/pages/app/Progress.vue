<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import Icon from '@/components/fixa/Icon.vue';
import PageState from '@/components/fixa/PageState.vue';
import type { PageStatus } from '@/components/fixa/PageState.vue';
import { t } from '@/i18n';
import { shortDate } from '@/lib/lessons';
import { weekdayName } from '@/lib/plan';

/**
 * Progresso (J5-F5, protótipo tela 12): sequência, congelamentos, meta da
 * semana, nível com barra e histórico simples. Sem ranking (GAM-05).
 */
const props = defineProps<{
    status: PageStatus;
    streak?: {
        current: number;
        longest: number;
        freezes: number;
        max_freezes: number;
    };
    week?: {
        days: { date: string; studied: boolean; status: string }[];
        studied: number;
        goal: number;
    };
    level?: {
        level: number;
        xp: number;
        in_level: number;
        to_next: number;
        per_level: number;
    };
    history?: { start: string; end: string; days: number; minutes: number }[];
}>();

const maxMinutes = computed(() =>
    Math.max(1, ...(props.history ?? []).map((week) => week.minutes)),
);

function dayName(date: string): string {
    const name = weekdayName(date);

    return name.charAt(0).toUpperCase() + name.slice(1);
}
</script>

<template>
    <Head :title="t('nav.progress')" />

    <h1 class="m-0 type-display-l">{{ t('nav.progress') }}</h1>

    <PageState :status="status">
        <div
            v-if="streak && week && level && history"
            class="grid gap-4 lg:grid-cols-2"
        >
            <div class="flex flex-col gap-4">
                <Card
                    as="section"
                    aria-labelledby="progress-streak"
                    class="flex flex-col gap-3"
                >
                    <h2 id="progress-streak" class="m-0 type-subtitle">
                        {{ t('progress.streakTitle') }}
                    </h2>
                    <div class="flex items-center gap-3">
                        <Icon
                            name="local_fire_department"
                            :filled="streak.current > 0"
                            :size="44"
                            class="text-ambd"
                        />
                        <div>
                            <p class="m-0 font-display text-4xl leading-none">
                                {{ streak.current }}
                            </p>
                            <p class="m-0 text-sm text-ink2">
                                {{
                                    streak.current === 0
                                        ? t('streak.restart')
                                        : streak.current === 1
                                          ? t('streak.one')
                                          : t('streak.many')
                                }}
                            </p>
                        </div>
                    </div>
                    <p class="m-0 text-sm">
                        {{
                            streak.longest === 1
                                ? t('progress.recordOne')
                                : t('progress.record', { n: streak.longest })
                        }}
                    </p>
                    <div
                        class="flex items-start gap-3 rounded-m bg-infot p-3 text-infod"
                    >
                        <span class="flex gap-0.5" aria-hidden="true">
                            <Icon
                                v-for="slot in streak.max_freezes"
                                :key="slot"
                                :name="
                                    slot <= streak.freezes
                                        ? 'ac_unit'
                                        : 'radio_button_unchecked'
                                "
                                :size="22"
                            />
                        </span>
                        <p class="m-0 text-sm">
                            {{
                                t('progress.freezesInfo', {
                                    n: streak.freezes,
                                    max: streak.max_freezes,
                                })
                            }}
                        </p>
                    </div>
                </Card>

                <Card
                    as="section"
                    aria-labelledby="progress-week"
                    class="flex flex-col gap-3"
                >
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 id="progress-week" class="m-0 type-subtitle">
                            {{ t('progress.weekTitle') }}
                        </h2>
                        <span class="text-sm font-bold">{{
                            t('progress.weekCount', {
                                n: week.studied,
                                goal: week.goal,
                            })
                        }}</span>
                    </div>
                    <ol
                        :aria-label="t('progress.weekLabel')"
                        class="m-0 grid list-none grid-cols-7 gap-1.5 p-0"
                    >
                        <li
                            v-for="day in week.days"
                            :key="day.date"
                            :aria-label="
                                day.studied
                                    ? t('progress.dayStudied', {
                                          day: dayName(day.date),
                                      })
                                    : t('progress.dayOpen', {
                                          day: dayName(day.date),
                                      })
                            "
                            class="flex flex-col items-center gap-1"
                        >
                            <span
                                class="grid size-9 place-items-center rounded-full"
                                :class="[
                                    day.studied
                                        ? 'bg-okt text-okd'
                                        : 'bg-sf2 text-ink2',
                                    day.status === 'today'
                                        ? 'border-2 border-prid'
                                        : '',
                                ]"
                                aria-hidden="true"
                            >
                                <Icon
                                    :name="day.studied ? 'check' : 'remove'"
                                    :size="18"
                                />
                            </span>
                            <span
                                class="text-xs font-bold text-ink2"
                                aria-hidden="true"
                                >{{ dayName(day.date).charAt(0) }}</span
                            >
                        </li>
                    </ol>
                </Card>
            </div>

            <div class="flex flex-col gap-4">
                <Card
                    as="section"
                    aria-labelledby="progress-level"
                    class="flex flex-col gap-3"
                >
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 id="progress-level" class="m-0 type-subtitle">
                            {{ t('progress.levelTitle', { n: level.level }) }}
                        </h2>
                        <span
                            class="inline-flex items-center gap-1 font-extrabold text-ambd"
                        >
                            <Icon name="star" filled :size="20" />{{
                                t('progress.xpTotal', { n: level.xp })
                            }}
                        </span>
                    </div>
                    <div
                        role="progressbar"
                        :aria-label="t('progress.levelBar')"
                        aria-valuemin="0"
                        :aria-valuemax="level.per_level"
                        :aria-valuenow="level.in_level"
                        class="h-3.5 overflow-hidden rounded-full bg-sf2"
                    >
                        <div
                            class="h-full rounded-full bg-pri motion-safe:transition-[width] motion-safe:duration-700"
                            :style="{
                                width: `${(level.in_level / level.per_level) * 100}%`,
                            }"
                        />
                    </div>
                    <p class="m-0 text-sm">
                        {{
                            t('progress.levelLine', {
                                in: level.in_level,
                                per: level.per_level,
                                left: level.to_next,
                                next: level.level + 1,
                            })
                        }}
                    </p>
                    <p class="m-0 text-sm text-ink2">
                        {{ t('progress.xpNote') }}
                    </p>
                </Card>

                <Card
                    as="section"
                    aria-labelledby="progress-history"
                    class="flex flex-col gap-3"
                >
                    <h2 id="progress-history" class="m-0 type-subtitle">
                        {{ t('progress.historyTitle') }}
                    </h2>
                    <ul class="m-0 flex list-none flex-col gap-2.5 p-0">
                        <li
                            v-for="row in history"
                            :key="row.start"
                            class="grid grid-cols-[6.5rem_minmax(0,1fr)] items-center gap-3 text-sm"
                        >
                            <span class="font-bold tabular-nums"
                                >{{ shortDate(row.start) }}–{{
                                    shortDate(row.end)
                                }}</span
                            >
                            <span class="flex items-center gap-2">
                                <span
                                    class="h-2.5 rounded-full bg-pri"
                                    :style="{
                                        width: `${Math.max(4, (row.minutes / maxMinutes) * 60)}%`,
                                    }"
                                    aria-hidden="true"
                                />
                                <span class="text-ink2">{{
                                    row.days === 1
                                        ? t('progress.historyRowOne', {
                                              minutes: row.minutes,
                                          })
                                        : t('progress.historyRow', {
                                              days: row.days,
                                              minutes: row.minutes,
                                          })
                                }}</span>
                            </span>
                        </li>
                    </ul>
                    <p class="m-0 text-sm text-ink2">
                        {{ t('progress.noRanking') }}
                    </p>
                </Card>
            </div>
        </div>

        <template #empty>
            <Card>
                <EmptyState icon="insights" :title="t('empty.progressTitle')">
                    {{ t('empty.progressBody') }}
                    <template #action>
                        <Button href="/hoje" variant="secondary" class="mt-2">{{
                            t('empty.todayAction')
                        }}</Button>
                    </template>
                </EmptyState>
            </Card>
        </template>
    </PageState>
</template>
