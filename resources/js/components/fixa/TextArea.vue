<script setup lang="ts">
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';
import Icon from './Icon.vue';

/**
 * Campo de texto longo, com o mesmo contrato do TextField: rótulo, dica e erro.
 */
const props = withDefaults(
    defineProps<{
        label: string;
        error?: string;
        hint?: string;
        id?: string;
        rows?: number;
    }>(),
    { error: undefined, hint: undefined, id: undefined, rows: 6 },
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
        <textarea
            :id="inputId"
            v-model="model"
            v-bind="$attrs"
            :rows="rows"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error || hint ? messageId : undefined"
            :class="
                cn(
                    'w-full resize-y rounded-s border-[1.5px] border-line bg-sf px-3.5 py-3 text-base leading-relaxed text-ink placeholder:text-ink3',
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
