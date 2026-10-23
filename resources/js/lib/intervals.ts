import { t } from '@/i18n';

/**
 * Intervalo previsto em cada botão do RateBar (FLA-03). Zero é "volta ainda
 * nesta sessão".
 */
export function intervalLabel(days: number): string {
    if (days <= 0) {
        return t('flashcards.lessMinute');
    }

    if (days === 1) {
        return t('flashcards.day');
    }

    if (days < 30) {
        return t('flashcards.days', { n: days });
    }

    if (days < 365) {
        const months = Math.round(days / 30);

        return months === 1
            ? t('flashcards.month')
            : t('flashcards.months', { n: months });
    }

    return t('flashcards.year');
}
