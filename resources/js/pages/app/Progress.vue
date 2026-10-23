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
    <Head :title="t('nav.progress')" />

    <h1 class="m-0 type-display-l">{{ t('nav.progress') }}</h1>

    <PageState :status="status">
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
