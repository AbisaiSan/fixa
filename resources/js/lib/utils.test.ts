import { describe, expect, it } from 'vite-plus/test';
import { cn } from './utils';

describe('cn', () => {
    it('merges conflicting tailwind classes keeping the last one', () => {
        expect(cn('px-2 py-1', 'px-4')).toBe('py-1 px-4');
    });

    it('ignores falsy values', () => {
        expect(cn('block', null, undefined, false)).toBe('block');
    });
});
