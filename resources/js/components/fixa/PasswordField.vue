<script setup lang="ts">
import { computed, ref, useId } from 'vue';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import Icon from './Icon.vue';

/**
 * Campo de senha com botão de mostrar e esconder, acessível por teclado.
 */
const props = withDefaults(
    defineProps<{
        label: string;
        name: string;
        error?: string;
        hint?: string;
        id?: string;
        autocomplete?: string;
    }>(),
    {
        error: undefined,
        hint: undefined,
        id: undefined,
        autocomplete: 'current-password',
    },
);

const visible = ref(false);
const generatedId = useId();
const inputId = computed(() => props.id ?? generatedId);
const messageId = computed(() => `${inputId.value}-message`);
</script>

<template>
    <div>
        <label :for="inputId" class="mb-1.5 block text-sm font-bold">{{
            label
        }}</label>
        <div class="relative">
            <input
                :id="inputId"
                v-bind="$attrs"
                :name="name"
                :type="visible ? 'text' : 'password'"
                :autocomplete="autocomplete"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="error || hint ? messageId : undefined"
                :class="
                    cn(
                        'min-h-12.5 w-full rounded-s border-[1.5px] border-line bg-sf pr-13 pl-3.5 text-base text-ink',
                        'focus:border-prid focus:shadow-[0_0_0_3px_var(--prit)] focus:outline-none',
                        error && 'border-errd',
                    )
                "
            />
            <button
                type="button"
                class="absolute top-1/2 right-1 grid size-11 -translate-y-1/2 place-items-center rounded-s text-ink2 hover:bg-sf2"
                :aria-pressed="visible"
                :aria-controls="inputId"
                @click="visible = !visible"
            >
                <Icon :name="visible ? 'visibility_off' : 'visibility'" />
                <span class="sr-only">{{
                    visible ? t('auth.hidePassword') : t('auth.showPassword')
                }}</span>
            </button>
        </div>
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
