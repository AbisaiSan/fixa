import { describe, expect, it } from 'vitest';
import { countWords, isProcessing, shortDate, sourceLabel } from './lessons';

describe('lessons', () => {
    it('formats the lesson date as day and month', () => {
        expect(shortDate('2026-10-21')).toBe('21/10');
    });

    it('describes the source with the page count', () => {
        expect(sourceLabel('text', null)).toBe('Texto colado');
        expect(sourceLabel('pdf', 4)).toBe('PDF · 4 páginas');
        expect(sourceLabel('pdf', 1)).toBe('PDF · 1 página');
    });

    it('counts words like the server', () => {
        expect(countWords('')).toBe(0);
        expect(countWords("I've worked here since 2022. Não é?")).toBe(7);
    });

    it('knows which statuses are still processing', () => {
        expect(isProcessing('generating')).toBe(true);
        expect(isProcessing('ready')).toBe(false);
        expect(isProcessing('failed')).toBe(false);
    });
});
