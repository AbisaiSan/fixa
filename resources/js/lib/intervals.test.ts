import { describe, expect, it } from 'vite-plus/test';
import { intervalLabel } from './intervals';

describe('intervalLabel', () => {
    it.each([
        [0, '<1 min'],
        [1, '1 dia'],
        [6, '6 dias'],
        [35, '1 mês'],
        [90, '3 meses'],
        [365, '1 ano'],
    ])('%i days reads %s', (days, label) => {
        expect(intervalLabel(days)).toBe(label);
    });
});
