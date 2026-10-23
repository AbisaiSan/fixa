<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Card from '@/components/fixa/Card.vue';
import Icon from '@/components/fixa/Icon.vue';
import { t } from '@/i18n';

/**
 * Limite diário de correções por IA atingido (J4-F4, ATV-05). Só a IA
 * para: o que tem gabarito fixo continua liberado.
 */
defineEmits<{ close: [] }>();

const page = usePage();
const limit = computed(() => page.props.aiLimit?.limit ?? 30);

const options = [
    {
        href: '/praticar/flashcards',
        icon: 'style',
        title: 'aiLimit.flashcards',
        meta: 'aiLimit.flashcardsMeta',
    },
    {
        href: '/praticar/ponto-fraco',
        icon: 'adjust',
        title: 'aiLimit.weakPoint',
        meta: 'aiLimit.weakPointMeta',
    },
    {
        href: '/praticar',
        icon: 'chrome_reader_mode',
        title: 'aiLimit.reading',
        meta: 'aiLimit.readingMeta',
    },
] as const;
</script>

<template>
    <Card
        as="section"
        role="alert"
        aria-labelledby="ai-limit-title"
        class="flex flex-col gap-4"
    >
        <button
            type="button"
            class="grid size-11 place-items-center self-end rounded-s text-ink2 hover:bg-sf2"
            :aria-label="t('aiLimit.close')"
            @click="$emit('close')"
        >
            <Icon name="close" />
        </button>
        <span
            class="grid size-16 place-items-center self-center rounded-full bg-ambt text-ambd"
            aria-hidden="true"
        >
            <Icon name="hourglass_bottom" :size="32" />
        </span>
        <h1 id="ai-limit-title" class="m-0 text-center type-title">
            {{ t('aiLimit.title', { n: limit }) }}
        </h1>
        <p class="m-0 text-center text-ink2">{{ t('aiLimit.body') }}</p>
        <ul class="m-0 flex list-none flex-col gap-2 p-0">
            <li v-for="option in options" :key="option.href">
                <Link
                    :href="option.href"
                    class="flex items-center gap-3 rounded-m border-[1.5px] border-line bg-sf p-3.5 text-ink no-underline hover:border-ink3"
                >
                    <Icon :name="option.icon" class="text-prid" />
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="font-bold">{{ t(option.title) }}</span>
                        <span class="text-sm text-ink2">{{
                            t(option.meta)
                        }}</span>
                    </span>
                    <Icon name="chevron_right" class="text-ink2" />
                </Link>
            </li>
        </ul>
    </Card>
</template>
