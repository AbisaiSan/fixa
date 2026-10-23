<script setup lang="ts">
import Icon from './Icon.vue';

/**
 * Estado vazio com a ilustração de formas simples do sistema de design.
 */
withDefaults(defineProps<{ icon?: string; title: string }>(), {
    icon: 'task_alt',
});
</script>

<template>
    <div class="flex flex-col items-center gap-2.5 px-5 py-6 text-center">
        <div aria-hidden="true" class="relative h-28 w-33">
            <span
                class="absolute top-3.5 left-2.5 size-24 rounded-full bg-prit"
            />
            <span
                class="absolute top-1 right-1.5 size-7.5 rounded-full bg-ambt"
            />
            <span
                class="absolute top-7.5 left-8 grid h-16 w-18 -rotate-6 place-items-center rounded-2xl border-[1.5px] border-line bg-sf shadow-sh1"
            >
                <Icon :name="icon" :size="34" class="text-prid" />
            </span>
            <span
                class="absolute right-4 bottom-2 size-3.5 rotate-45 rounded-sm bg-amb"
            />
        </div>
        <p class="m-0 text-lg font-extrabold">{{ title }}</p>
        <div class="max-w-70 text-sm leading-normal text-ink2"><slot /></div>
        <slot name="action" />
    </div>
</template>
