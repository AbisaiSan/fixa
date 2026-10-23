import { describe, expect, it } from 'vite-plus/test';
import { nextTick } from 'vue';
import { focusFirstError } from './forms';

describe('focusFirstError', () => {
    it('moves focus to the first invalid field of the form', async () => {
        document.body.innerHTML = `
            <form id="f">
                <input id="ok" />
                <input id="first" aria-invalid="true" />
                <input id="second" aria-invalid="true" />
            </form>
            <input id="outside" aria-invalid="true" />`;

        focusFirstError(document.getElementById('f'));
        await nextTick();

        expect(document.activeElement?.id).toBe('first');
    });

    it('does nothing when there is no invalid field', async () => {
        document.body.innerHTML = '<form><input id="ok" /></form>';
        (document.getElementById('ok') as HTMLInputElement).focus();

        focusFirstError();
        await nextTick();

        expect(document.activeElement?.id).toBe('ok');
    });
});
