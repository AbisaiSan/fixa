<script setup lang="ts">
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import { t } from '@/i18n';
import Icon from './Icon.vue';

/**
 * Folha que sobe da base (celular) com foco preso e Esc para fechar.
 */
defineProps<{ title: string; description?: string }>();

const open = defineModel<boolean>('open', { default: false });
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-40 bg-ink/40" />
            <DialogContent
                class="fixed inset-x-0 bottom-0 z-50 mx-auto max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-t-xl bg-sf p-6 text-ink shadow-sh2"
            >
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <DialogTitle class="m-0 type-subtitle">{{
                            title
                        }}</DialogTitle>
                        <DialogDescription
                            v-if="description"
                            class="mt-1 text-sm text-ink2"
                        >
                            {{ description }}
                        </DialogDescription>
                    </div>
                    <DialogClose
                        class="grid size-11 shrink-0 place-items-center rounded-s text-ink2 hover:bg-sf2"
                    >
                        <Icon name="close" />
                        <span class="sr-only">{{ t('common.close') }}</span>
                    </DialogClose>
                </div>
                <slot />
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
