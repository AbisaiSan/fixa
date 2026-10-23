import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import AiLimitBanner from './AiLimitBanner.vue';
import AiLimitReached from './AiLimitReached.vue';

const props = { aiLimit: { limit: 30, reached: true } };

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        usePage: () => ({
            props: { targetLang: { code: 'en' }, ...props },
            url: '/hoje',
        }),
    };
});

describe('AI limit', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('the banner says only the AI stopped', async () => {
        const wrapper = mount(AiLimitBanner, { attachTo: document.body });

        expect(wrapper.text()).toContain('Correções de IA voltam amanhã.');
        expect(wrapper.text()).toContain('Flashcards, leitura, ponto fraco');
        await expectAccessible(wrapper.element);
    });

    it('the limit screen lists what stays available', async () => {
        const wrapper = mount(AiLimitReached, { attachTo: document.body });

        expect(wrapper.text()).toContain(
            'Você usou as 30 correções de IA de hoje',
        );
        expect(
            wrapper.findAll('a').map((link) => link.attributes('href')),
        ).toEqual([
            '/praticar/flashcards',
            '/praticar/ponto-fraco',
            '/praticar',
        ]);
        await expectAccessible(wrapper.element);
    });

    it('without the limit the banner stays hidden', () => {
        props.aiLimit.reached = false;

        expect(mount(AiLimitBanner).text()).toBe('');
    });
});
