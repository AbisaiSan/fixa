<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Banner from '@/components/fixa/Banner.vue';
import Card from '@/components/fixa/Card.vue';
import { t } from '@/i18n';

defineProps<{
    title: string;
    version: string;
    draft: boolean;
    sections: { title: string; paragraphs: string[] }[];
}>();
</script>

<template>
    <Head :title="title" />

    <main class="mx-auto flex min-h-dvh max-w-2xl flex-col gap-6 px-5 py-10">
        <Link href="/" class="font-display text-2xl text-ink no-underline">{{
            $page.props.name
        }}</Link>
        <header>
            <h1 class="m-0 type-display-l">{{ title }}</h1>
            <p class="mt-2 text-sm text-ink2">
                {{ t('legal.version', { version }) }}
            </p>
        </header>

        <Banner
            v-if="draft"
            tone="warning"
            icon="edit_note"
            :title="t('legal.draftTitle')"
        >
            {{ t('legal.draftBody') }}
        </Banner>

        <Card as="article" class="flex flex-col gap-6">
            <section v-for="section in sections" :key="section.title">
                <h2 class="mt-0 mb-2 type-subtitle">{{ section.title }}</h2>
                <p
                    v-for="paragraph in section.paragraphs"
                    :key="paragraph"
                    class="m-0 mb-3 text-ink type-body"
                >
                    {{ paragraph }}
                </p>
            </section>
        </Card>
    </main>
</template>
