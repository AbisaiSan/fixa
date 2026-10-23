<script setup lang="ts">
import { computed } from 'vue';
import { t } from '@/i18n';
import Icon from './Icon.vue';

export type StatusKind =
    | 'nova'
    | 'aprendendo'
    | 'dominada'
    | 'feito'
    | 'hoje'
    | 'protegido'
    | 'perdido'
    | 'total'
    | 'parcial'
    | 'erro';

const props = defineProps<{ kind: StatusKind }>();

// A forma do ícone muda com o estado (vazio, metade, cheio): nunca só a cor.
const styles: Record<
    StatusKind,
    { icon: string; filled?: boolean; colors: string; pill?: boolean }
> = {
    nova: {
        icon: 'radio_button_unchecked',
        colors: 'bg-infot text-infod',
        pill: true,
    },
    aprendendo: { icon: 'contrast', colors: 'bg-ambt text-ambd', pill: true },
    dominada: {
        icon: 'check_circle',
        filled: true,
        colors: 'bg-okt text-okd',
        pill: true,
    },
    feito: { icon: 'check_circle', filled: true, colors: 'bg-okt text-okd' },
    hoje: { icon: 'today', colors: 'bg-prit text-prid' },
    protegido: { icon: 'ac_unit', colors: 'bg-infot text-infod' },
    perdido: { icon: 'remove', colors: 'bg-sf2 text-ink2' },
    total: { icon: 'check_circle', filled: true, colors: 'bg-okt text-okd' },
    parcial: { icon: 'contrast', colors: 'bg-ambt text-ambd' },
    erro: { icon: 'cancel', colors: 'bg-errt text-errd' },
};

const style = computed(() => styles[props.kind]);
</script>

<template>
    <span
        class="inline-flex items-center gap-1 px-2.25 py-1 text-[12.5px] font-extrabold"
        :class="[style.colors, style.pill ? 'rounded-full' : 'rounded-xs']"
    >
        <Icon :name="style.icon" :filled="style.filled" :size="15" />{{
            t(`status.${kind}`)
        }}
    </span>
</template>
