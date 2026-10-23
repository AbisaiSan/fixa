import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import GenerationProgress from './GenerationProgress.vue';

describe('GenerationProgress', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('marks the finished, current and next steps', async () => {
        const wrapper = mount(GenerationProgress, {
            props: { status: 'generating' },
            attachTo: document.body,
        });
        const steps = wrapper.findAll('li');

        expect(steps).toHaveLength(4);
        expect(steps[0].text()).toContain('concluído');
        expect(steps[1].text()).toContain('concluído');
        expect(steps[2].attributes('aria-current')).toBe('step');
        expect(steps[2].text()).toContain('Escrevendo a revisão');
        expect(steps[3].text()).toContain('a seguir');
        expect(wrapper.get('section').attributes('aria-busy')).toBe('true');
        await expectAccessible(wrapper.element);
    });
});
