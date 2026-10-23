<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import Icon from './Icon.vue';

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';

const props = withDefaults(
    defineProps<{
        variant?: ButtonVariant;
        icon?: string;
        loading?: boolean;
        disabled?: boolean;
        type?: 'button' | 'submit' | 'reset';
        href?: string;
        block?: boolean;
    }>(),
    {
        variant: 'primary',
        icon: undefined,
        loading: false,
        disabled: false,
        type: 'button',
        href: undefined,
        block: false,
    },
);

const variants: Record<ButtonVariant, string> = {
    primary:
        'min-h-13 px-5 bg-prid text-onpri font-extrabold text-base hover:brightness-108',
    secondary:
        'min-h-12 px-5 bg-sf text-ink font-bold text-[15px] border-[1.5px] border-line hover:border-ink3',
    ghost: 'min-h-11 px-3 bg-transparent text-prid font-extrabold hover:bg-prit',
    danger: 'min-h-11 px-3.5 bg-errd text-onpri font-extrabold hover:brightness-108',
};

const inactive = computed(() => props.disabled || props.loading);

const classes = computed(() =>
    cn(
        'inline-flex items-center justify-center gap-2 transition duration-(--dur-fast) ease-out active:scale-98',
        variants[props.variant],
        props.variant === 'primary' || props.variant === 'secondary'
            ? 'rounded-m'
            : 'rounded-s',
        props.block && 'w-full',
        inactive.value && 'cursor-not-allowed opacity-45 active:scale-100',
    ),
);
</script>

<template>
    <Link v-if="href && !inactive" :href="href" :class="classes">
        <Icon
            v-if="icon"
            :name="icon"
            :filled="variant === 'primary'"
            :size="variant === 'ghost' ? 20 : 24"
        />
        <slot />
    </Link>
    <button
        v-else
        :type="type"
        :class="classes"
        :disabled="inactive"
        :aria-busy="loading || undefined"
    >
        <Icon v-if="loading" name="progress_activity" class="animate-spin" />
        <Icon
            v-else-if="icon"
            :name="icon"
            :filled="variant === 'primary'"
            :size="variant === 'ghost' ? 20 : 24"
        />
        <slot />
        <span v-if="loading" class="sr-only">{{ t('common.loading') }}</span>
    </button>
</template>
