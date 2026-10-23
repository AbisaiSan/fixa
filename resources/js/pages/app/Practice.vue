<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import Icon from '@/components/fixa/Icon.vue';
import PageState from '@/components/fixa/PageState.vue';
import type { PageStatus } from '@/components/fixa/PageState.vue';
import { t } from '@/i18n';

/**
 * Praticar (protótipo, aba Praticar): os flashcards do dia. Escrita,
 * leitura e ponto fraco entram na J4.
 */
const props = defineProps<{
    status: PageStatus;
    flashcards?: { due: number; new: number };
}>();

const meta = computed(() => {
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
</script>

<template>
    <Head :title="t('nav.practice')" />

    <h1 class="m-0 type-display-l">{{ t('nav.practice') }}</h1>

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

        <p class="m-0 text-ink2">{{ t('practice.intro') }}</p>
        <ul class="m-0 flex list-none flex-col gap-3 p-0">
            <li>
                <Link
                    href="/praticar/flashcards"
                    class="flex items-center gap-4 rounded-l border-[1.5px] border-line bg-sf p-4 text-ink no-underline shadow-sh1 transition duration-(--dur-fast) hover:border-ink3"
                >
                    <span
                        class="grid size-12 shrink-0 place-items-center rounded-m bg-prit text-prid"
                        aria-hidden="true"
                    >
                        <Icon name="style" />
                    </span>
                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span class="font-extrabold">{{
                            t('flashcards.title')
                        }}</span>
                        <span class="text-sm text-ink2">{{ meta }}</span>
                    </span>
                    <Icon name="chevron_right" class="text-ink2" />
                </Link>
            </li>
        </ul>
        <p class="m-0 text-sm text-ink2">{{ t('practice.soon') }}</p>
    </PageState>
</template>
