import { describe, expect, it } from 'vite-plus/test';
import { diffWords } from './wordDiff';

describe('diffWords', () => {
    it('marks what changed on each side', () => {
        const diff = diffWords(
            'I work here since 2021.',
            'I have worked here since 2021.',
        );

        expect(
            diff.answer
                .filter((token) => token.changed)
                .map((token) => token.text),
        ).toEqual(['work']);
        expect(
            diff.corrected
                .filter((token) => token.changed)
                .map((token) => token.text),
        ).toEqual(['have', 'worked']);
    });

    it('ignores case, punctuation and apostrophe style', () => {
        const diff = diffWords(
            'i’ve never worked in sales',
            "I've never worked in sales.",
        );

        expect(diff.answer.every((token) => !token.changed)).toBe(true);
    });
});
