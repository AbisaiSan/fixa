<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import Icon from '@/components/fixa/Icon.vue';
import TargetText from '@/components/fixa/TargetText.vue';
import { t } from '@/i18n';
import type { StringKey } from '@/i18n';
import { shortDate } from '@/lib/lessons';

/**
 * Caderno de erros (J4-F6, protótipo tela 10, ERR-01 a ERR-03): pontos por
 * categoria, repetições, ciclo de 1, 3 e 7 dias e acertos para sair.
 */
type Category = 'grammar' | 'vocabulary' | 'preposition' | 'spelling';

type MistakeRow = {
    ulid: string;
    category: Category;
    priority: boolean;
    repetitions: number;
    answer: string;
    corrected: string;
    explanation: string;
    step: number;
    correct_streak: number;
    due_in_days: number | null;
    lesson_date: string | null;
};

const props = defineProps<{
    filter: Category | null;
    counts: Record<'all' | Category, number>;
    mistakes: MistakeRow[];
}>();

const categories: Record<Category, { label: StringKey; icon: string }> = {
    grammar: { label: 'mistakes.grammar', icon: 'rule' },
    vocabulary: { label: 'mistakes.vocabulary', icon: 'translate' },
    preposition: { label: 'mistakes.preposition', icon: 'link' },
    spelling: { label: 'mistakes.spelling', icon: 'spellcheck' },
};

const chips = computed(() => [
    {
        value: null,
        icon: 'apps',
        label: t('mistakes.chip', {
            label: t('mistakes.all'),
            n: props.counts.all,
        }),
    },
    ...(Object.keys(categories) as Category[]).map((value) => ({
        value,
        icon: categories[value].icon,
        label: t('mistakes.chip', {
            label: t(categories[value].label),
            n: props.counts[value],
        }),
    })),
]);

function pick(value: Category | null): void {
    router.get(
        '/praticar/caderno-de-erros',
        value ? { categoria: value } : {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function when(days: number | null): string {
    if (days === null || days <= 0) {
        return t('mistakes.today');
    }

    return days === 1
        ? t('mistakes.tomorrow')
        : t('mistakes.inDays', { n: days });
}

const cycle = ['mistakes.day1', 'mistakes.day3', 'mistakes.day7'] as const;
</script>

<template>
    <Head :title="t('mistakes.title')" />

    <h1 class="m-0 type-display-l">{{ t('mistakes.title') }}</h1>

    <Card v-if="counts.all === 0">
        <EmptyState icon="auto_stories" :title="t('mistakes.emptyTitle')">
            {{ t('mistakes.emptyBody') }}
        </EmptyState>
    </Card>

    <template v-else>
        <p class="m-0 text-ink2">{{ t('mistakes.intro') }}</p>
        <Button
            href="/praticar/ponto-fraco"
            icon="play_arrow"
            class="self-start"
            >{{ t('mistakes.practice') }}</Button
        >

        <div
            role="group"
            :aria-label="t('mistakes.filter')"
            class="flex flex-wrap gap-2"
        >
            <button
                v-for="chip in chips"
                :key="chip.value ?? 'all'"
                type="button"
                :aria-pressed="filter === chip.value"
                class="inline-flex min-h-10 items-center gap-1.5 rounded-full border-[1.5px] px-3.5 text-sm font-bold"
                :class="
                    filter === chip.value
                        ? 'border-prid bg-prit text-prid'
                        : 'border-line bg-sf text-ink2'
                "
                @click="pick(chip.value)"
            >
                <Icon :name="chip.icon" :size="17" />{{ chip.label }}
            </button>
        </div>

        <p v-if="mistakes.length === 0" class="m-0 rounded-m bg-sf2 p-4">
            {{ t('mistakes.none') }}
        </p>

        <ul class="m-0 flex list-none flex-col gap-3 p-0">
            <li v-for="mistake in mistakes" :key="mistake.ulid">
                <Card as="article" class="flex flex-col gap-3">
                    <div
                        class="flex flex-wrap items-center gap-2 text-[12.5px] font-bold"
                    >
                        <span
                            class="inline-flex items-center gap-1 rounded-xs bg-sf2 px-2.25 py-1 text-ink2"
                        >
                            <Icon
                                :name="categories[mistake.category].icon"
                                :size="15"
                            />{{ t(categories[mistake.category].label) }}
                        </span>
                        <span
                            v-if="mistake.priority"
                            class="inline-flex items-center gap-1 rounded-xs bg-errt px-2.25 py-1 text-errd"
                        >
                            <Icon name="priority_high" :size="15" />{{
                                t('mistakes.priority')
                            }}
                        </span>
                        <span class="text-ink2">{{
                            t('mistakes.repeated', { n: mistake.repetitions })
                        }}</span>
                        <span
                            v-if="mistake.lesson_date"
                            class="ml-auto text-ink2"
                            >{{
                                t('mistakes.fromLesson', {
                                    date: shortDate(mistake.lesson_date),
                                })
                            }}</span
                        >
                    </div>

                    <p class="m-0 flex items-start gap-2">
                        <Icon
                            name="close"
                            :size="20"
                            class="mt-0.5 text-errd"
                        />
                        <span class="sr-only">{{ t('mistakes.wrong') }}:</span>
                        <TargetText class="line-through decoration-errd">{{
                            mistake.answer
                        }}</TargetText>
                    </p>
                    <p class="m-0 flex items-start gap-2">
                        <Icon name="check" :size="20" class="mt-0.5 text-okd" />
                        <span class="sr-only">{{ t('mistakes.right') }}:</span>
                        <TargetText class="font-bold">{{
                            mistake.corrected
                        }}</TargetText>
                    </p>
                    <p class="m-0 text-sm text-ink2">
                        {{ mistake.explanation }}
                    </p>

                    <div
                        class="flex flex-wrap items-center gap-3 border-t border-line pt-3 text-sm"
                    >
                        <span class="font-bold">{{
                            t('mistakes.next', {
                                when: when(mistake.due_in_days),
                            })
                        }}</span>
                        <ol
                            class="m-0 flex list-none gap-1.5 p-0"
                            :aria-label="t('mistakes.cycle')"
                        >
                            <li
                                v-for="(label, step) in cycle"
                                :key="label"
                                :aria-current="
                                    step === mistake.step ? 'step' : undefined
                                "
                                class="inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-[12.5px] font-bold"
                                :class="
                                    step < mistake.step
                                        ? 'bg-okt text-okd'
                                        : step === mistake.step
                                          ? 'bg-prit text-prid'
                                          : 'bg-sf2 text-ink2'
                                "
                            >
                                <Icon
                                    v-if="step < mistake.step"
                                    name="check"
                                    :size="14"
                                />{{ t(label) }}
                            </li>
                        </ol>
                        <span
                            class="ml-auto inline-flex items-center gap-1.5 text-ink2"
                        >
                            <span class="flex gap-0.5" aria-hidden="true">
                                <span
                                    v-for="bar in 3"
                                    :key="bar"
                                    class="h-2 w-4 rounded-full"
                                    :class="
                                        bar <= mistake.correct_streak
                                            ? 'bg-okd'
                                            : 'bg-line'
                                    "
                                />
                            </span>
                            {{
                                t('mistakes.streak', {
                                    n: mistake.correct_streak,
                                })
                            }}
                        </span>
                    </div>
                </Card>
            </li>
        </ul>
    </template>
</template>
