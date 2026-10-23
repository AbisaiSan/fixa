<script setup lang="ts">
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';
import Icon from './Icon.vue';

export type SelectOption = { value: string; label: string; disabled?: boolean };

const props = withDefaults(
    defineProps<{
        label: string;
        options: SelectOption[];
        error?: string;
        id?: string;
    }>(),
    { error: undefined, id: undefined },
);

const model = defineModel<string>({ default: '' });

const generatedId = useId();
const selectId = computed(() => props.id ?? generatedId);
const messageId = computed(() => `${selectId.value}-message`);
</script>

<template>
    <div>
        <label :for="selectId" class="mb-1.5 block text-sm font-bold">{{
            label
        }}</label>
        <div class="relative">
            <select
                :id="selectId"
                v-model="model"
                v-bind="$attrs"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="error ? messageId : undefined"
                :class="
                    cn(
                        'min-h-12.5 w-full appearance-none rounded-s border-[1.5px] border-line bg-sf pr-11 pl-3.5 text-base text-ink',
                        'focus:border-prid focus:shadow-[0_0_0_3px_var(--prit)] focus:outline-none',
                        error && 'border-errd',
                    )
                "
            >
                <option
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                    :disabled="option.disabled"
                >
                    {{ option.label }}
                </option>
            </select>
            <Icon
                name="expand_more"
                class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-ink2"
            />
        </div>
        <p
            v-if="error"
            :id="messageId"
            class="mt-1.5 flex items-center gap-1.5 text-[13.5px] font-semibold text-errd"
        >
            <Icon name="error" :size="17" />{{ error }}
        </p>
    </div>
</template>

<script lang="ts">
export default { inheritAttrs: false };
</script>
