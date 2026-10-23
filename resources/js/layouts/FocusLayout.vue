<script setup lang="ts">
import AppShell from '@/components/fixa/AppShell.vue';

/**
 * Sessões de prática em modo foco: sem navegação, só a tarefa (J3-F1).
 */
</script>

<template>
    <AppShell focus>
        <div class="mx-auto flex max-w-150 flex-col gap-5">
            <slot />
        </div>
    </AppShell>
</template>
