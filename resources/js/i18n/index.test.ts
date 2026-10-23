import { describe, expect, it } from 'vite-plus/test';
import { languageName, t } from './index';

describe('t', () => {
    it('returns interface strings and replaces parameters', () => {
        expect(t('nav.today')).toBe('Hoje');
        expect(t('origin.lesson', { date: '21/10' })).toBe('Da aula de 21/10');
    });

    it('names the target language without hardcoding it', () => {
        expect(languageName('en')).toBe('inglês');
        expect(languageName('xx')).toBe('xx');
    });
});
