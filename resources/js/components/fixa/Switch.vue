<script setup lang="ts">
import { SwitchRoot, SwitchThumb } from 'reka-ui';
import { useId } from 'vue';
import Icon from './Icon.vue';

defineProps<{ label: string; disabled?: boolean }>();

const model = defineModel<boolean>({ default: false });
const labelId = useId();
</script>

<template>
    <div class="flex min-h-11 items-center justify-between gap-3">
        <span :id="labelId" class="font-bold">{{ label }}</span>
        <SwitchRoot
            v-model="model"
            :disabled="disabled"
            :aria-labelledby="labelId"
            class="flex h-8.5 w-14 shrink-0 items-center rounded-full bg-line p-0.75 transition-colors duration-(--dur-fast) disabled:opacity-45 data-[state=checked]:bg-prid"
        >
            <SwitchThumb
                class="grid size-7 place-items-center rounded-full bg-sf shadow-sh1 transition-transform duration-(--dur-fast) ease-out data-[state=checked]:translate-x-5.5 data-[state=checked]:bg-onpri"
            >
                <Icon v-if="model" name="check" :size="18" class="text-prid" />
            </SwitchThumb>
        </SwitchRoot>
    </div>
</template>
