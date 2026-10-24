import { describe, expect, it } from 'vite-plus/test';
import { ptBR } from '@/i18n/pt-BR';

// Sem punição (J5-B8, GAM-05): retomada incentiva, sem culpa e sem contar
// dias perdidos. O único número permitido é o teto de minutos do plano.
const GUILT = [
    /perd(eu|ido|idos)/i,
    /falt(ou|aram)/i,
    /d[ií]vida/i,
    /atras/i,
    /culpa/i,
    /falh/i,
];

describe('resume messages', () => {
    const messages = Object.entries(ptBR.resume);

    it('exist for the codes the server sends', () => {
        expect(ptBR.resume.welcome_back).toBeTruthy();
        expect(ptBR.resume.freeze_used).toBeTruthy();
    });

    it.each(messages)('"%s" carries no guilt', (_key, text) => {
        for (const word of GUILT) {
            expect(text).not.toMatch(word);
        }
    });

    it.each(messages)('"%s" never counts days', (_key, text) => {
        expect(text.replace('{max}', '')).not.toMatch(/\d/);
    });
});
