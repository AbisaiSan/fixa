<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Banner from '@/components/fixa/Banner.vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import Icon from '@/components/fixa/Icon.vue';
import Toast from '@/components/fixa/Toast.vue';
import { t } from '@/i18n';
import { blockIcon, blockTitle, weekdayName } from '@/lib/plan';
import type { PlanBlock } from '@/types/plan';

type DayStatus = 'done' | 'today' | 'frozen' | 'missed' | 'planned';

type PlanDayTask = Pick<
    PlanBlock,
    'ulid' | 'kind' | 'activity' | 'minutes' | 'done' | 'moved_from'
> & { lesson: { title: string; lesson_date: string } | null };

type PlanDay = {
    date: string;
    status: DayStatus;
    goal: number;
    minutes: number;
    tasks: PlanDayTask[];
};

/**
 * Plano da semana (J5-F4, protótipo tela 11, fluxo C): os 7 dias com
 * ícones diferentes por estado, os blocos do dia escolhido e a meta.
 * Dia perdido não vira cobrança: só diz que está tudo bem (GAM-05).
 */
const props = defineProps<{
    today: string;
    weekStart: string;
    goal: number;
    goalRange: { min: number; max: number; step: number };
    redone: boolean;
    days: PlanDay[];
}>();

// A forma do ícone muda com o estado, nunca só a cor.
const STATES: Record<
    DayStatus,
    { icon: string; filled: boolean; colors: string }
> = {
    done: { icon: 'check_circle', filled: true, colors: 'bg-okt text-okd' },
    today: { icon: 'today', filled: true, colors: 'bg-prit text-prid' },
    frozen: { icon: 'ac_unit', filled: false, colors: 'bg-infot text-infod' },
    missed: { icon: 'remove', filled: false, colors: 'bg-sf2 text-ink2' },
    planned: {
        icon: 'radio_button_unchecked',
        filled: false,
        colors: 'bg-sf text-ink2',
    },
};

const selected = ref(
    props.days.find((day) => day.date === props.today)?.date ??
        props.days[0]?.date,
);
const day = computed(() =>
    props.days.find((item) => item.date === selected.value),
);

function dayNumber(date: string): string {
    return String(Number(date.slice(8, 10)));
}

function shortWeekday(date: string): string {
    const name = weekdayName(date);

    return name.charAt(0).toUpperCase() + name.slice(1, 3);
}

function longDay(date: string): string {
    const name = weekdayName(date);

    return `${name.charAt(0).toUpperCase()}${name.slice(1)}, ${dayNumber(date)}`;
}

function monthDay(date: string): string {
    const [year, month, dayOfMonth] = date.split('-').map(Number);

    return new Intl.DateTimeFormat('pt-BR', {
        day: 'numeric',
        month: 'long',
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(year, month - 1, dayOfMonth, 12)));
}

const weekEnd = computed(() => {
    const [year, month, dayOfMonth] = props.weekStart.split('-').map(Number);

    return new Date(Date.UTC(year, month - 1, dayOfMonth + 6, 12))
        .toISOString()
        .slice(0, 10);
});

function dayLabel(item: PlanDay): string {
    return item.status === 'planned'
        ? t('plan.dayMinutes', { n: item.minutes })
        : t(`plan.states.${item.status}`);
}

function dayAria(item: PlanDay): string {
    return t('plan.dayAria', {
        day: longDay(item.date),
        state:
            item.status === 'planned'
                ? t('plan.plannedAria', { n: item.minutes })
                : t(`plan.states.${item.status}`),
    });
}

const resting = computed(
    () => day.value?.status === 'frozen' || day.value?.status === 'missed',
);

function taskBlock(task: PlanDayTask): PlanBlock {
    return {
        ...task,
        count: 0,
        skipped: false,
        href: null,
        lesson: task.lesson ? { ulid: '', ...task.lesson } : null,
    };
}

// Meta diária (PLA-01): de 5 a 30 minutos, salva ao soltar o controle.
const goal = ref(props.goal);
const toast = ref<string | null>(null);
watch(
    () => props.goal,
    (value) => (goal.value = value),
);

function saveGoal(): void {
    if (goal.value === props.goal) {
        return;
    }

    router.patch(
        '/plano/meta',
        { daily_goal_minutes: goal.value },
        {
            preserveScroll: true,
            onSuccess: () =>
                (toast.value = t('plan.goalSaved', { n: goal.value })),
        },
    );
}

const ticks = computed(() => {
    const list: number[] = [];

    for (
        let n = props.goalRange.min;
        n <= props.goalRange.max;
        n += props.goalRange.step
    ) {
        list.push(n);
    }

    return list;
});
</script>

<template>
    <Head :title="t('plan.title')" />

    <Banner
        v-if="redone"
        tone="info"
        icon="autorenew"
        :title="t('resume.plan_redone_title')"
        role="status"
        >{{
            t('resume.plan_redone_body', {
                max: Math.round(goal * 1.5),
            })
        }}</Banner
    >

    <div>
        <h1 class="m-0 type-title">
            {{
                t('plan.week', {
                    from: monthDay(weekStart),
                    to: monthDay(weekEnd),
                })
            }}
        </h1>
        <p class="m-0 text-sm text-ink2">
            {{ t('plan.generated', { goal: props.goal }) }}
        </p>
    </div>

    <ol
        :aria-label="t('plan.daysLabel')"
        class="m-0 grid list-none grid-cols-7 gap-1.5 p-0"
    >
        <li v-for="item in days" :key="item.date">
            <button
                type="button"
                :aria-pressed="item.date === selected"
                :aria-label="dayAria(item)"
                class="flex min-h-11 w-full flex-col items-center gap-1 rounded-m px-1 py-2 text-ink"
                :class="[
                    STATES[item.status].colors,
                    item.date === selected
                        ? 'border-2 border-prid'
                        : 'border border-line',
                ]"
                @click="selected = item.date"
            >
                <span class="text-xs font-bold">{{
                    shortWeekday(item.date)
                }}</span>
                <span class="font-display text-lg leading-none">{{
                    dayNumber(item.date)
                }}</span>
                <Icon
                    :name="STATES[item.status].icon"
                    :filled="STATES[item.status].filled"
                    :size="20"
                />
                <span class="text-[11px] leading-tight font-bold">{{
                    dayLabel(item)
                }}</span>
            </button>
        </li>
    </ol>

    <div
        class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)]"
    >
        <Card
            v-if="day"
            as="section"
            aria-labelledby="plan-day-title"
            class="flex flex-col gap-3"
        >
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="plan-day-title" class="m-0 type-subtitle">
                    {{ longDay(day.date)
                    }}{{
                        day.status === 'today' ? ` · ${t('plan.today')}` : ''
                    }}
                </h2>
                <span class="text-sm text-ink2">{{
                    resting
                        ? t('plan.nothing')
                        : `${t('plan.dayMinutes', { n: day.minutes })}${day.minutes > day.goal ? ` · ${t('plan.includesMoved')}` : ''}`
                }}</span>
            </div>

            <p
                v-if="day.status === 'frozen'"
                class="m-0 flex items-start gap-2 rounded-m bg-infot p-3 text-sm text-infod"
            >
                <Icon name="ac_unit" :size="20" />{{ t('plan.frozenNote') }}
            </p>
            <p
                v-else-if="day.status === 'missed'"
                class="m-0 flex items-start gap-2 rounded-m bg-sf2 p-3 text-sm text-ink2"
            >
                <Icon name="event_busy" :size="20" />{{ t('plan.missedNote') }}
            </p>

            <ul v-if="!resting" class="m-0 flex list-none flex-col gap-2 p-0">
                <li
                    v-for="task in day.tasks"
                    :key="task.ulid"
                    class="flex items-center gap-3 rounded-m bg-sf2 p-3"
                >
                    <Icon
                        :name="
                            task.done
                                ? 'check_circle'
                                : blockIcon(taskBlock(task))
                        "
                        :filled="task.done"
                        :class="task.done ? 'text-okd' : 'text-prid'"
                    />
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="font-bold">{{
                            blockTitle(taskBlock(task))
                        }}</span>
                        <span
                            v-if="task.moved_from"
                            class="inline-flex items-center gap-1 text-sm text-infod"
                            ><Icon name="redo" :size="16" />{{
                                t('today.movedFrom', {
                                    day: weekdayName(task.moved_from),
                                })
                            }}</span
                        >
                    </span>
                    <span class="text-sm text-ink2 tabular-nums">{{
                        t('plan.dayMinutes', { n: task.minutes })
                    }}</span>
                </li>
            </ul>

            <Button
                v-if="day.status === 'today'"
                href="/hoje"
                icon="play_arrow"
                block
                >{{
                    day.tasks.some((task) => task.done)
                        ? t('plan.continueDay')
                        : t('plan.startDay')
                }}</Button
            >
        </Card>

        <Card
            as="section"
            aria-labelledby="plan-goal-title"
            class="flex flex-col gap-3"
        >
            <div class="flex items-baseline justify-between gap-2">
                <h2 id="plan-goal-title" class="m-0 type-subtitle">
                    <label for="plan-goal">{{ t('plan.goalLabel') }}</label>
                </h2>
                <span class="font-display text-2xl"
                    >{{ goal }}
                    <span class="text-sm text-ink2">{{
                        t('plan.goalUnit')
                    }}</span></span
                >
            </div>
            <input
                id="plan-goal"
                v-model.number="goal"
                type="range"
                :min="goalRange.min"
                :max="goalRange.max"
                :step="goalRange.step"
                :aria-valuetext="t('plan.goalAria', { n: goal })"
                class="w-full accent-prid"
                @change="saveGoal"
            />
            <div
                class="flex justify-between text-xs text-ink2"
                aria-hidden="true"
            >
                <span v-for="tick in ticks" :key="tick">{{ tick }}</span>
            </div>
            <p class="m-0 text-sm text-ink2">
                {{ t('plan.goalNote', { max: Math.round(goal * 1.5) }) }}
            </p>
        </Card>
    </div>

    <Toast v-if="toast">{{ toast }}</Toast>
</template>
