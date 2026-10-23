import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import PasswordField from './PasswordField.vue';

describe('PasswordField', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('toggles visibility with an accessible button', async () => {
        const wrapper = mount(PasswordField, {
            props: { label: 'Senha', name: 'password' },
            attachTo: document.body,
        });
        const input = wrapper.get('input');
        const toggle = wrapper.get('button');

        expect(input.attributes('type')).toBe('password');
        expect(toggle.attributes('aria-pressed')).toBe('false');
        expect(toggle.text()).toContain('Mostrar senha');

        await toggle.trigger('click');

        expect(input.attributes('type')).toBe('text');
        expect(toggle.attributes('aria-pressed')).toBe('true');
        expect(toggle.text()).toContain('Esconder senha');
        await expectAccessible(wrapper.element);
    });

    it('exposes the error to assistive technology', async () => {
        const wrapper = mount(PasswordField, {
            props: {
                label: 'Senha',
                name: 'password',
                error: 'A senha precisa ter pelo menos um número.',
            },
            attachTo: document.body,
        });
        const input = wrapper.get('input');

        expect(input.attributes('aria-invalid')).toBe('true');
        expect(
            wrapper.get(`#${input.attributes('aria-describedby')}`).text(),
        ).toContain('pelo menos um número');
        await expectAccessible(wrapper.element);
    });
});
