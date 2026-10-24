import { t } from '@/i18n';
import { shortDate } from '@/lib/lessons';
import type { PlanBlock } from '@/types/plan';

/**
 * Textos e ícones dos blocos do plano (J5-F1), a partir do tipo e da
 * atividade. Os textos ficam todos em pt-BR.ts.
 */
type BlockType =
    | 'flashcards'
    | 'writing'
    | 'reading'
    | 'vocabulary'
    | 'weak_point';

const ICONS: Record<BlockType, string> = {
    flashcards: 'style',
    writing: 'edit_note',
    reading: 'chrome_reader_mode',
    vocabulary: 'translate',
    weak_point: 'adjust',
};

function typeOf(block: PlanBlock): BlockType {
    return block.kind === 'lesson' ? (block.activity ?? 'writing') : block.kind;
}

export function blockIcon(block: PlanBlock): string {
    return ICONS[typeOf(block)];
}

export function blockTitle(block: PlanBlock): string {
    const type = typeOf(block);

    return t(`blocks.${type}`, {
        title: block.lesson?.title ?? '',
        date: block.lesson ? shortDate(block.lesson.lesson_date) : '',
    });
}

export function blockShort(block: PlanBlock): string {
    return t(`blocks.short_${typeOf(block)}`);
}

export function blockMeta(block: PlanBlock): string {
    if (block.skipped) {
        return t('today.blockNothing');
    }

    return t(`blocks.meta_${typeOf(block)}`, {
        n: block.count,
        min: block.minutes,
    });
}

/**
 * Dia da semana sem o "-feira" ("quarta"), a partir de "2026-10-28".
 */
export function weekdayName(isoDate: string): string {
    const [year, month, day] = isoDate.split('-').map(Number);
    const date = new Date(Date.UTC(year, month - 1, day, 12));

    return new Intl.DateTimeFormat('pt-BR', {
        weekday: 'long',
        timeZone: 'UTC',
    })
        .format(date)
        .replace('-feira', '');
}
