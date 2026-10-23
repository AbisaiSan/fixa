<script setup lang="ts">
import { computed } from 'vue';
import Card from '@/components/fixa/Card.vue';
import Icon from '@/components/fixa/Icon.vue';
import Skeleton from '@/components/fixa/Skeleton.vue';
import { t } from '@/i18n';
import type { LessonStatus } from '@/types/lessons';

/**
 * Progresso da geração em 4 etapas (J2-F3, J2-B11). A tela consulta o estado
 * por polling; o aluno pode sair e voltar, porque o trabalho roda na fila.
 */
const props = defineProps<{ status: LessonStatus }>();

const order = ['queued', 'extracting', 'generating', 'validating'] as const;

const steps = computed(() => {
    const current = order.indexOf(props.status as (typeof order)[number]);

    return order.map((step, index) => ({
        step,
        label: t(`generation.${step}`),
        state:
            index < current
                ? ('done' as const)
                : index === current
                  ? ('current' as const)
                  : ('pending' as const),
    }));
});
</script>

<template>
    <Card
        as="section"
        aria-labelledby="generation-title"
        aria-busy="true"
        class="flex flex-col gap-5"
    >
        <div>
            <h2 id="generation-title" class="m-0 type-title">
                {{ t('generation.title') }}
            </h2>
            <p class="mt-1 mb-0 text-ink2">{{ t('generation.body') }}</p>
        </div>

        <ol
            class="m-0 flex list-none flex-col gap-3 p-0"
            :aria-label="t('generation.stepsLabel')"
            aria-live="polite"
        >
            <li
                v-for="item in steps"
                :key="item.step"
                class="flex items-center gap-3"
                :aria-current="item.state === 'current' ? 'step' : undefined"
            >
                <Icon
                    v-if="item.state === 'done'"
                    name="check_circle"
                    filled
                    class="text-okd"
                />
                <span
                    v-else-if="item.state === 'current'"
                    class="grid size-6 place-items-center"
                    aria-hidden="true"
                >
                    <span
                        class="size-3 animate-pulse rounded-full bg-prid motion-reduce:animate-none"
                    />
                </span>
                <Icon v-else name="radio_button_unchecked" class="text-ink2" />
                <span
                    :class="
                        item.state === 'pending'
                            ? 'text-ink2'
                            : 'font-bold text-ink'
                    "
                    >{{ item.label
                    }}<span v-if="item.state === 'current'" aria-hidden="true"
                        >…</span
                    ></span
                >
                <span class="sr-only">{{ t(`generation.${item.state}`) }}</span>
            </li>
        </ol>

        <div class="rounded-m bg-sf2 p-4">
            <Skeleton :label="t('generation.preview')" :lines="4" />
        </div>
    </Card>
</template>
