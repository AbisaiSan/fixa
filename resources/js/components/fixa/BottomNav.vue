<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { t } from '@/i18n';
import { isActive, mainNav } from '@/lib/navigation';
import Icon from './Icon.vue';

const page = usePage();
</script>

<template>
    <nav
        :aria-label="t('nav.label')"
        class="grid grid-cols-5 border-t border-line bg-sf px-1 pt-1.5 pb-[calc(10px+env(safe-area-inset-bottom))]"
    >
        <Link
            v-for="item in mainNav"
            :key="item.href"
            :href="item.href"
            :aria-current="isActive(page.url, item.href) ? 'page' : undefined"
            class="flex min-h-14 flex-col items-center gap-0.75 py-1 text-[11.5px]"
            :class="
                isActive(page.url, item.href)
                    ? 'font-extrabold text-prid'
                    : 'font-semibold text-ink2'
            "
        >
            <span
                aria-hidden="true"
                class="grid h-7.5 w-14 place-items-center rounded-full"
                :class="{ 'bg-prit': isActive(page.url, item.href) }"
            >
                <Icon
                    :name="item.icon"
                    :size="22"
                    :filled="isActive(page.url, item.href)"
                />
            </span>
            {{ t(item.label) }}
        </Link>
    </nav>
</template>
