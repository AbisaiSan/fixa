import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import TargetText from './TargetText.vue';

describe('TargetText', () => {
    it('marks content with the target language and the serif style', async () => {
        const wrapper = mount(TargetText, {
            slots: { default: 'They haven’t called me yet.' },
            attachTo: document.body,
        });

        expect(wrapper.attributes('lang')).toBe('en');
        expect(wrapper.classes()).toContain('type-target');
        await expectAccessible(wrapper.element);
        wrapper.unmount();
    });

    it('accepts another language, tag and size', () => {
        const wrapper = mount(TargetText, {
            props: { lang: 'es', as: 'p', size: 'large' },
            slots: { default: 'Hola' },
        });

        expect(wrapper.element.tagName).toBe('P');
        expect(wrapper.attributes('lang')).toBe('es');
        expect(wrapper.classes()).toContain('type-target-l');
    });
});
