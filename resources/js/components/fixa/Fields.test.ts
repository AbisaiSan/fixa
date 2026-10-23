import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import Checkbox from './Checkbox.vue';
import Select from './Select.vue';
import Switch from './Switch.vue';
import TextField from './TextField.vue';

describe('form fields', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('TextField links label, value and hint', async () => {
        const wrapper = mount(TextField, {
            props: {
                label: 'E-mail',
                type: 'email',
                modelValue: 'a@b.com',
                hint: 'Usamos para entrar.',
            },
            attachTo: document.body,
        });
        const input = wrapper.get('input');

        expect(wrapper.get('label').attributes('for')).toBe(
            input.attributes('id'),
        );
        expect(input.element.value).toBe('a@b.com');
        expect(input.attributes('aria-describedby')).toBeDefined();
        await expectAccessible(wrapper.element);
    });

    it('TextField exposes the error to assistive technology', async () => {
        const wrapper = mount(TextField, {
            props: {
                label: 'Senha',
                type: 'password',
                error: 'Use pelo menos 8 caracteres.',
            },
            attachTo: document.body,
        });
        const input = wrapper.get('input');
        const message = wrapper.get(`#${input.attributes('aria-describedby')}`);

        expect(input.attributes('aria-invalid')).toBe('true');
        expect(message.text()).toContain('Use pelo menos 8 caracteres.');
        await expectAccessible(wrapper.element);
    });

    it('TextField updates the model', async () => {
        const wrapper = mount(TextField, {
            props: {
                label: 'Nome',
                modelValue: '',
                'onUpdate:modelValue': (value: string) =>
                    wrapper.setProps({ modelValue: value }),
            },
        });

        await wrapper.get('input').setValue('Marina');

        expect(wrapper.props('modelValue')).toBe('Marina');
    });

    it('Select renders options with a label', async () => {
        const wrapper = mount(Select, {
            props: {
                label: 'Idioma',
                modelValue: 'en',
                options: [
                    { value: 'en', label: 'Inglês' },
                    {
                        value: 'es',
                        label: 'Espanhol (em breve)',
                        disabled: true,
                    },
                ],
            },
            attachTo: document.body,
        });

        expect(wrapper.findAll('option')).toHaveLength(2);
        expect(wrapper.get('select').element.value).toBe('en');
        await expectAccessible(wrapper.element);
    });

    it('Checkbox is a labelled native checkbox', async () => {
        const wrapper = mount(Checkbox, {
            props: { modelValue: true },
            slots: {
                default: 'Declaro que tenho direito de usar este material',
            },
            attachTo: document.body,
        });

        expect(wrapper.get('input').element.checked).toBe(true);
        await expectAccessible(wrapper.element);
    });

    it('Switch is a labelled switch that toggles', async () => {
        const wrapper = mount(Switch, {
            props: {
                label: 'Lembrete diário',
                modelValue: false,
                'onUpdate:modelValue': (value: boolean) =>
                    wrapper.setProps({ modelValue: value }),
            },
            attachTo: document.body,
        });
        const control = wrapper.get('[role="switch"]');

        expect(control.attributes('aria-checked')).toBe('false');
        await control.trigger('click');
        expect(wrapper.props('modelValue')).toBe(true);
        await expectAccessible(wrapper.element);
    });
});
