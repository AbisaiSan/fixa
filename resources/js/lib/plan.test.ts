import { describe, expect, it } from 'vite-plus/test';
import type { PlanBlock } from '@/types/plan';
import {
    blockIcon,
    blockMeta,
    blockShort,
    blockTitle,
    weekdayName,
} from './plan';

const block = (overrides: Partial<PlanBlock> = {}): PlanBlock => ({
    ulid: '01j',
    kind: 'lesson',
    activity: 'writing',
    minutes: 4,
    count: 6,
    done: false,
    skipped: false,
    moved_from: null,
    href: '/praticar/escrita/01j',
    lesson: {
        ulid: '01l',
        title: 'Present Perfect',
        lesson_date: '2026-10-21',
    },
    ...overrides,
});

describe('plan blocks', () => {
    it('names the lesson activity with the lesson', () => {
        expect(blockTitle(block())).toBe('Escrita: Present Perfect');
        expect(blockTitle(block({ activity: 'vocabulary' }))).toBe(
            'Vocabulário da aula de 21/10',
        );
        expect(blockShort(block({ activity: 'reading' }))).toBe(
            'leitura da aula',
        );
        expect(blockIcon(block({ kind: 'weak_point', activity: null }))).toBe(
            'adjust',
        );
    });

    it('describes the size of the block, or that there is nothing today', () => {
        expect(blockMeta(block())).toBe('6 frases · 4 min');
        expect(
            blockMeta(
                block({
                    kind: 'flashcards',
                    activity: null,
                    count: 8,
                    minutes: 3,
                }),
            ),
        ).toBe('8 cartões · 3 min');
        expect(blockMeta(block({ skipped: true, count: 0 }))).toBe(
            'Nada para hoje',
        );
    });

    it('names the weekday without "-feira"', () => {
        expect(weekdayName('2026-10-28')).toBe('quarta');
        expect(weekdayName('2026-10-25')).toBe('domingo');
    });
});
