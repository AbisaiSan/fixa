<script setup lang="ts">
import AppShell from '@/components/fixa/AppShell.vue';

/**
 * Layout das telas do app: o AppShell do sistema de design (J1-F2).
 */
withDefaults(defineProps<{ focus?: boolean }>(), { focus: false });
</script>

<template>
    <AppShell :focus="focus">
        <div class="mx-auto flex max-w-270 flex-col gap-6">
            <slot />
        </div>
    </AppShell>
</template>
