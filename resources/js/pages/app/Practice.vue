<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import PageState from '@/components/fixa/PageState.vue';
import type { PageStatus } from '@/components/fixa/PageState.vue';
import { t } from '@/i18n';

defineProps<{ status: PageStatus; hasLessons?: boolean }>();
</script>

<template>
    <Head :title="t('nav.practice')" />

    <h1 class="m-0 type-display-l">{{ t('nav.practice') }}</h1>

    <PageState :status="status">
        <template #empty>
            <Card v-if="hasLessons">
                <EmptyState
                    icon="edit_note"
                    :title="t('empty.practiceSoonTitle')"
                >
                    {{ t('empty.practiceSoonBody') }}
                    <template #action>
                        <Button
                            href="/aulas"
                            variant="secondary"
                            class="mt-2"
                            >{{ t('empty.lessonsAction') }}</Button
                        >
                    </template>
                </EmptyState>
            </Card>
            <Card v-else>
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
    </PageState>
</template>
