<script setup lang="ts">
import { t } from '@/i18n';
import BottomNav from './BottomNav.vue';
import OfflineBanner from './OfflineBanner.vue';
import SidebarNav from './SidebarNav.vue';

/**
 * Moldura das telas: BottomNav abaixo de 1024 px e Sidebar a partir de 1024 px (J0-F5).
 * Em modo foco (sessões de prática) a navegação some.
 */
withDefaults(defineProps<{ focus?: boolean }>(), { focus: false });
</script>

<template>
    <div class="flex min-h-dvh bg-bg text-ink">
        <a
            href="#conteudo"
            class="sr-only z-50 rounded-s bg-sf px-4 py-3 font-bold focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
            >{{ t('nav.skipToContent') }}</a
        >

        <div v-if="!focus" class="sticky top-0 hidden h-dvh shrink-0 lg:block">
            <SidebarNav />
        </div>

        <main
            id="conteudo"
            tabindex="-1"
            class="min-w-0 flex-1 px-5 py-6 lg:px-10 lg:py-8"
            :class="{ 'pb-28 lg:pb-8': !focus }"
        >
            <OfflineBanner class="mb-4" />
            <slot />
        </main>

        <div v-if="!focus" class="fixed inset-x-0 bottom-0 z-30 lg:hidden">
            <BottomNav />
        </div>
    </div>
</template>
