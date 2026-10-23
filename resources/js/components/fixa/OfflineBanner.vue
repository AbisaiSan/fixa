<script setup lang="ts">
import { useOnline } from '@/composables/useOnline';
import { t } from '@/i18n';
import Banner from './Banner.vue';

const online = useOnline();
</script>

<template>
    <div aria-live="polite">
        <Banner v-if="!online" icon="wifi_off" :title="t('state.offlineTitle')">
            {{ t('state.offlineBody') }}
        </Banner>
    </div>
</template>
