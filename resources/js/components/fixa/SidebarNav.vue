<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { t } from '@/i18n';
import { isActive, mainNav } from '@/lib/navigation';
import Button from './Button.vue';
import Icon from './Icon.vue';

const page = usePage();
</script>

<template>
    <nav
        :aria-label="t('nav.label')"
        class="flex h-full w-62 flex-col gap-1 border-r border-line bg-sf px-4 pt-5.5 pb-4.5"
    >
        <div class="flex items-center gap-2.5 px-2.5 pb-4.5">
            <span
                aria-hidden="true"
                class="grid size-8 place-items-center rounded-[10px] bg-prid font-display text-xl leading-none text-onpri"
                >f</span
            >
            <span class="font-display text-2xl tracking-tight">{{
                page.props.name
            }}</span>
        </div>

        <Link
            v-for="item in mainNav"
            :key="item.href"
            :href="item.href"
            :aria-current="isActive(page.url, item.href) ? 'page' : undefined"
            class="flex min-h-11.5 items-center gap-3 rounded-s px-3 text-[15px] transition-colors duration-(--dur-fast) ease-out"
            :class="
                isActive(page.url, item.href)
                    ? 'bg-prit font-extrabold text-prid'
                    : 'font-semibold text-ink2 hover:bg-sf2 hover:text-ink'
            "
        >
            <Icon
                :name="item.icon"
                :size="22"
                :filled="isActive(page.url, item.href)"
            />
            {{ t(item.label) }}
        </Link>

        <div class="flex-1" />

        <Button
            variant="secondary"
            icon="upload"
            href="/aulas/enviar"
            class="mb-3 text-prid"
        >
            {{ t('nav.upload') }}
        </Button>
        <Link
            href="/configuracoes"
            :aria-current="
                isActive(page.url, '/configuracoes') ? 'page' : undefined
            "
            class="flex min-h-14 items-center gap-2.5 rounded-m px-2 font-semibold text-ink2 hover:bg-sf2 hover:text-ink"
        >
            <Icon name="settings" :size="22" />
            {{ t('nav.profile') }}
        </Link>
    </nav>
</template>
