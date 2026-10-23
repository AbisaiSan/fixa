import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import Button from './Button.vue';

describe('Button', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it.each(['primary', 'secondary', 'ghost', 'danger'] as const)(
        'renders the %s variant accessibly',
        async (variant) => {
            const wrapper = mount(Button, {
                props: { variant, icon: 'play_arrow' },
                slots: { default: 'Começar' },
                attachTo: document.body,
            });

            expect(wrapper.text()).toContain('Começar');
            expect(wrapper.attributes('type')).toBe('button');
            await expectAccessible(wrapper.element);
        },
    );

    it('emits click when enabled', async () => {
        const wrapper = mount(Button, { slots: { default: 'Salvar' } });

        await wrapper.trigger('click');

        expect(wrapper.emitted('click')).toHaveLength(1);
    });

    it('is disabled and busy while loading', async () => {
        const wrapper = mount(Button, {
            props: { loading: true },
            slots: { default: 'Salvar' },
            attachTo: document.body,
        });

        expect(wrapper.attributes('disabled')).toBeDefined();
        expect(wrapper.attributes('aria-busy')).toBe('true');
        expect(wrapper.text()).toContain('Carregando');
        await expectAccessible(wrapper.element);
    });

    it('does not render a link when disabled', () => {
        const wrapper = mount(Button, {
            props: { href: '/hoje', disabled: true },
            slots: { default: 'Hoje' },
        });

        expect(wrapper.element.tagName).toBe('BUTTON');
    });
});
