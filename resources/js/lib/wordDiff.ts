/**
 * Marca as palavras que mudaram entre a resposta e a versão corrigida
 * (J4-F3), como no protótipo: na resposta, o que não está na correção; na
 * correção, o que a resposta não tinha.
 */
export type DiffToken = { text: string; changed: boolean };

const normalize = (word: string) =>
    word
        .toLowerCase()
        .replace(/[’‘`]/g, "'")
        .replace(/^[^\p{L}\p{N}']+|[^\p{L}\p{N}']+$/gu, '');

function tokens(text: string): string[] {
    return text.split(/\s+/).filter(Boolean);
}

export function diffWords(
    answer: string,
    corrected: string,
): { answer: DiffToken[]; corrected: DiffToken[] } {
    const answerWords = new Set(tokens(answer).map(normalize));
    const correctedWords = new Set(tokens(corrected).map(normalize));

    return {
        answer: tokens(answer).map((text) => ({
            text,
            changed: !correctedWords.has(normalize(text)),
        })),
        corrected: tokens(corrected).map((text) => ({
            text,
            changed: !answerWords.has(normalize(text)),
        })),
    };
}
