<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import { t } from '@/i18n';
</script>

<template>
    <Head :title="t('upload.title')" />

    <h1 class="m-0 type-display-l">{{ t('upload.title') }}</h1>

    <Card>
        <EmptyState icon="upload_file" :title="t('upload.soonTitle')">
            {{ t('upload.soonBody') }}
            <template #action>
                <Button href="/hoje" variant="secondary" class="mt-2">{{
                    t('empty.todayAction')
                }}</Button>
            </template>
        </EmptyState>
    </Card>
</template>
