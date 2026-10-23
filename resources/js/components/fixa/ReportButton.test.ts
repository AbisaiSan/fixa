import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import ReportButton from './ReportButton.vue';

describe('ReportButton', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('reports and, once reported, shows under review with undo', async () => {
        const wrapper = mount(ReportButton, {
            props: { label: 'Reportar erro na pergunta 1' },
            attachTo: document.body,
        });

        expect(wrapper.get('button').attributes('aria-label')).toBe(
            'Reportar erro na pergunta 1',
        );
        await wrapper.get('button').trigger('click');
        expect(wrapper.emitted('report')).toHaveLength(1);
        await expectAccessible(wrapper.element);

        await wrapper.setProps({ reported: true });

        expect(wrapper.get('[role="status"]').text()).toContain('Em análise');
        await wrapper.get('button').trigger('click');
        expect(wrapper.emitted('undo')).toHaveLength(1);
        await expectAccessible(wrapper.element);
    });
});
