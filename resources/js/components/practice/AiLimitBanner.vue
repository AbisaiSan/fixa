<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Banner from '@/components/fixa/Banner.vue';
import { t } from '@/i18n';

/**
 * Aviso em Hoje e Praticar quando as correções por IA do dia acabaram
 * (J4-F4, ATV-05). Só aparece no limite.
 */
const page = usePage();
const reached = computed(() => page.props.aiLimit?.reached === true);
</script>

<template>
    <Banner
        v-if="reached"
        tone="warning"
        icon="hourglass_bottom"
        :title="t('aiLimit.bannerTitle')"
        >{{ t('aiLimit.bannerBody') }}</Banner
    >
</template>
