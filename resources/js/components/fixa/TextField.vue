<script setup lang="ts">
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';
import Icon from './Icon.vue';

const props = withDefaults(
    defineProps<{
        label: string;
        type?: string;
        error?: string;
        hint?: string;
        id?: string;
    }>(),
    { type: 'text', error: undefined, hint: undefined, id: undefined },
);

const model = defineModel<string>({ default: '' });

const generatedId = useId();
const inputId = computed(() => props.id ?? generatedId);
const messageId = computed(() => `${inputId.value}-message`);
</script>

<template>
    <div>
        <label :for="inputId" class="mb-1.5 block text-sm font-bold">{{
            label
        }}</label>
        <input
            :id="inputId"
            v-model="model"
            v-bind="$attrs"
            :type="type"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error || hint ? messageId : undefined"
            :class="
                cn(
                    'min-h-12.5 w-full rounded-s border-[1.5px] border-line bg-sf px-3.5 text-base text-ink placeholder:text-ink3',
                    'focus:border-prid focus:shadow-[0_0_0_3px_var(--prit)] focus:outline-none',
                    error && 'border-errd',
                )
            "
        />
        <p
            v-if="error"
            :id="messageId"
            class="mt-1.5 flex items-center gap-1.5 text-[13.5px] font-semibold text-errd"
        >
            <Icon name="error" :size="17" />{{ error }}
        </p>
        <p
            v-else-if="hint"
            :id="messageId"
            class="mt-1.5 text-[13.5px] text-ink2"
        >
            {{ hint }}
        </p>
    </div>
</template>

<script lang="ts">
export default { inheritAttrs: false };
</script>
