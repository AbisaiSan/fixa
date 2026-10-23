<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import PageState from '@/components/fixa/PageState.vue';
import type { PageStatus } from '@/components/fixa/PageState.vue';
import { t } from '@/i18n';

defineProps<{ status: PageStatus }>();
</script>

<template>
    <Head :title="t('nav.vocabulary')" />

    <h1 class="m-0 type-display-l">{{ t('nav.vocabulary') }}</h1>

    <PageState :status="status">
        <template #empty>
            <Card>
                <EmptyState
                    icon="translate"
                    :title="t('empty.vocabularyTitle')"
                >
                    {{ t('empty.vocabularyBody') }}
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
