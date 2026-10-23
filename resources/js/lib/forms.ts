import { nextTick } from 'vue';

/**
 * Depois de um envio com erro, leva o foco ao primeiro campo inválido (J1-F6),
 * para quem usa teclado ou leitor de tela chegar direto ao problema.
 */
export function focusFirstError(form?: HTMLElement | null): void {
    void nextTick(() => {
        const root = form ?? document;
        const field = root.querySelector<HTMLElement>('[aria-invalid="true"]');

        field?.focus();
    });
}
