import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';
import { languageName } from '@/i18n';

export type TargetLang = { code: string; name: string };

/**
 * Idioma estudado pelo aluno. Use targetLang.name nos textos, nunca "inglês" fixo.
 */
export function useTargetLang(): ComputedRef<TargetLang> {
    const page = usePage();

    return computed(() => {
        const code = page.props.targetLang?.code ?? 'en';

        return { code, name: languageName(code) };
    });
}
