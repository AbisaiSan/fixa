import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import ReportedNotice from './ReportedNotice.vue';

describe('ReportedNotice', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('announces the item under review and offers undo', async () => {
        const wrapper = mount(ReportedNotice, { attachTo: document.body });

        expect(wrapper.get('[role="status"]').text()).toContain(
            'Reportado · em análise',
        );

        await wrapper.get('button').trigger('click');

        expect(wrapper.emitted('undo')).toHaveLength(1);
        await expectAccessible(wrapper.element);
    });
});
