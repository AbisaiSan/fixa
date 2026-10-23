import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import type { SessionCard } from '@/types/lessons';
import Flashcards from './Flashcards.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        Head: { render: () => null },
        usePage: () => ({
            props: { targetLang: { code: 'en' } },
            url: '/praticar/flashcards',
        }),
    };
});

const card = (ulid: string, isNew: boolean): SessionCard => ({
    ulid,
    kind: 'word',
    front: `front ${ulid}`,
    back: `back ${ulid}`,
    example: null,
    example_translation: null,
    part_of_speech: null,
    lesson_date: '2026-10-21',
    is_new: isNew,
    preview: [0, 1, 2, 4],
});

function render(cards: SessionCard[]) {
    return mount(Flashcards, {
        props: { mode: 'normal', lesson: null, cards },
        attachTo: document.body,
    });
}

describe('Flashcards session', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.unstubAllGlobals();
    });

    it('works with the keyboard only and sends each answer', async () => {
        const fetch = vi.fn(
            async () =>
                new Response(
                    JSON.stringify({
                        card: 'a',
                        interval_days: 2,
                        preview: [0, 1, 2, 4],
                    }),
                ),
        );
        vi.stubGlobal('fetch', fetch);
        const wrapper = render([card('a', false), card('b', true)]);

        expect(wrapper.text()).toContain('Revisão vencida');
        expect(wrapper.text()).toContain('1 de 2');
        await expectAccessible(wrapper.element);

        await wrapper.get('[role="button"]').trigger('keydown', { key: ' ' });
        window.dispatchEvent(new KeyboardEvent('keydown', { key: '3' }));
        await flushPromises();

        expect(fetch).toHaveBeenCalledOnce();
        const [, init] = fetch.mock.calls[0] as unknown as [
            string,
            RequestInit,
        ];
        expect(JSON.parse(init.body as string)).toMatchObject({ rating: 3 });
        expect(wrapper.text()).toContain('front b');
        expect(wrapper.text()).toContain('Novo');
    });

    it('brings a missed card back at the end of the session', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(
                async () =>
                    new Response(
                        JSON.stringify({
                            card: 'a',
                            interval_days: 0,
                            preview: [0, 1, 2, 4],
                        }),
                    ),
            ),
        );
        const wrapper = render([card('a', true)]);

        await wrapper
            .get('[role="button"]')
            .trigger('keydown', { key: 'Enter' });
        window.dispatchEvent(new KeyboardEvent('keydown', { key: '1' }));
        await flushPromises();

        expect(wrapper.text()).toContain('front a');
        expect(wrapper.text()).toContain('2 de 2');
        expect(wrapper.text()).toContain('Revisão vencida');
    });

    it('shows an empty state with nothing to review', async () => {
        const wrapper = render([]);

        expect(wrapper.text()).toContain('Nada para revisar agora');
        await expectAccessible(wrapper.element);
    });
});
