<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { t } from '@/i18n';

/**
 * Layout das telas de acesso (J1-F1): painel da marca a partir de 1024 px e
 * formulário de até 400 px. Cada página informa título e descrição.
 */
const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <Head :title="title" />

    <div class="grid min-h-dvh grid-cols-1 bg-bg text-ink lg:grid-cols-2">
        <aside
            class="relative hidden flex-col justify-between overflow-hidden bg-prit p-14 lg:flex"
            aria-hidden="true"
        >
            <div class="flex items-center gap-2.5">
                <span
                    class="grid size-9 place-items-center rounded-[11px] bg-prid font-display text-[22px] text-onpri"
                    >f</span
                >
                <span class="font-display text-[26px]">{{
                    $page.props.name
                }}</span>
            </div>
            <div class="relative z-10">
                <p class="m-0 mb-4 max-w-115 tracking-tight type-display-xl">
                    {{ t('auth.brandTitle') }}
                </p>
                <p class="m-0 max-w-105 text-[17px] leading-relaxed text-ink2">
                    {{ t('auth.brandBody') }}
                </p>
            </div>
            <span
                class="absolute top-35 -right-20 size-80 rounded-full bg-sf opacity-55"
            />
            <span
                class="absolute top-27.5 right-17.5 size-14 rounded-full bg-ambt"
            />
            <p class="relative m-0 text-[13px] text-ink2">
                {{ t('auth.brandPrivacy') }}
            </p>
        </aside>

        <main class="flex items-center justify-center px-5 py-10 lg:p-14">
            <div class="flex w-full max-w-100 flex-col gap-4.5">
                <div class="mb-2 flex items-center gap-2.5 lg:hidden">
                    <span
                        aria-hidden="true"
                        class="grid size-8.5 place-items-center rounded-[11px] bg-prid font-display text-[21px] text-onpri"
                        >f</span
                    >
                    <span class="font-display text-[25px]">{{
                        $page.props.name
                    }}</span>
                </div>
                <header>
                    <h1 class="m-0 mb-1.5 font-display text-[34px] font-normal">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="m-0 text-ink2">
                        {{ description }}
                    </p>
                </header>
                <slot />
            </div>
        </main>
    </div>
</template>
