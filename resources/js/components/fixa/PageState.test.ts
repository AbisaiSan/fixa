import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { nextTick } from 'vue';
import { expectAccessible } from '@/test/axe';
import OfflineBanner from './OfflineBanner.vue';
import PageState from './PageState.vue';
import type { PageStatus } from './PageState.vue';

const slots = { default: '<p>Conteúdo</p>', empty: '<p>Vazio</p>' };

describe('PageState', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it.each<[PageStatus, string]>([
        ['ready', 'Conteúdo'],
        ['empty', 'Vazio'],
        ['loading', 'Carregando'],
        ['error', 'Não conseguimos carregar.'],
    ])('shows the %s state', async (status, text) => {
        const wrapper = mount(PageState, {
            props: { status },
            slots,
            attachTo: document.body,
        });

        expect(wrapper.text()).toContain(text);
        await expectAccessible(wrapper.element.parentElement as HTMLElement);
    });

    it('marks loading as busy', () => {
        const wrapper = mount(PageState, {
            props: { status: 'loading' },
            slots,
        });

        expect(wrapper.find('[aria-busy="true"]').exists()).toBe(true);
    });

    it('asks to retry on error', async () => {
        const wrapper = mount(PageState, { props: { status: 'error' }, slots });

        await wrapper.get('button').trigger('click');

        expect(wrapper.emitted('retry')).toHaveLength(1);
    });
});

describe('OfflineBanner', () => {
    it('appears when the browser goes offline and leaves when it comes back', async () => {
        const wrapper = mount(OfflineBanner, { attachTo: document.body });

        Object.defineProperty(navigator, 'onLine', {
            configurable: true,
            get: () => false,
        });
        window.dispatchEvent(new Event('offline'));
        await nextTick();
        expect(wrapper.text()).toContain('Sem conexão.');

        Object.defineProperty(navigator, 'onLine', {
            configurable: true,
            get: () => true,
        });
        window.dispatchEvent(new Event('online'));
        await nextTick();
        expect(wrapper.text()).not.toContain('Sem conexão.');

        wrapper.unmount();
    });
});
