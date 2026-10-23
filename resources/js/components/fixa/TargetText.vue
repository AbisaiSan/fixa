<script setup lang="ts">
import { computed } from 'vue';
import { useTargetLang } from '@/composables/useTargetLang';

/**
 * Texto no idioma estudado: serifa e atributo lang para leitores de tela e hifenização.
 */
const props = withDefaults(
    defineProps<{
        lang?: string;
        as?: string;
        size?: 'default' | 'large';
    }>(),
    { lang: undefined, as: 'span', size: 'default' },
);

const targetLang = useTargetLang();
const lang = computed(() => props.lang ?? targetLang.value.code);
</script>

<template>
    <component
        :is="as"
        :lang="lang"
        :class="size === 'large' ? 'type-target-l' : 'type-target'"
    >
        <slot />
    </component>
</template>
