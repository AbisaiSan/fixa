import { t } from '@/i18n';
import type { LessonSource, LessonStatus } from '@/types/lessons';

/**
 * Data da aula como aparece nas telas ("21/10"), a partir de "2026-10-21".
 * Sem fuso: a data da aula é só um dia do calendário.
 */
export function shortDate(isoDate: string): string {
    const [, month, day] = isoDate.split('-');

    return `${day}/${month}`;
}

export function sourceLabel(
    source: LessonSource,
    pages: number | null,
): string {
    if (source === 'text') {
        return t('lessons.sourceText');
    }

    if (pages === null || pages === 0) {
        return t('lessons.sourcePdf');
    }

    return pages === 1
        ? t('lessons.sourcePdfPage')
        : t('lessons.sourcePdfPages', { n: pages });
}

/**
 * Conta palavras como o servidor (WordCounter): sequências de letras e números.
 */
export function countWords(text: string): number {
    return text.match(/[\p{L}\p{N}][\p{L}\p{N}'’-]*/gu)?.length ?? 0;
}

export function isProcessing(status: LessonStatus): boolean {
    return ['queued', 'extracting', 'generating', 'validating'].includes(
        status,
    );
}
