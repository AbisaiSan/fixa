import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { nextTick } from 'vue';
import { expectAccessible } from '@/test/axe';
import Sheet from './Sheet.vue';

describe('Sheet', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('opens as a labelled dialog with a close button', async () => {
        mount(Sheet, {
            props: {
                open: true,
                title: 'Ver semana',
                description: 'Seu plano refeito.',
            },
            slots: { default: '<p>Conteúdo</p>' },
            attachTo: document.body,
        });
        await nextTick();

        const dialog = document.querySelector('[role="dialog"]');

        expect(dialog).not.toBeNull();
        expect(dialog?.getAttribute('aria-labelledby')).toBeTruthy();
        expect(document.body.textContent).toContain('Fechar');
        await expectAccessible(dialog as Element);
    });

    it('renders nothing when closed', () => {
        mount(Sheet, {
            props: { open: false, title: 'Ver semana' },
            attachTo: document.body,
        });

        expect(document.querySelector('[role="dialog"]')).toBeNull();
    });
});
