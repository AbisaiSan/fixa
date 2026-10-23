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

describe('Flashcards rush mode', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.useRealTimers();
    });

    it('counts down five minutes and ends the session when time is up', async () => {
        vi.useFakeTimers({ toFake: ['setInterval', 'clearInterval'] });
        const wrapper = mount(Flashcards, {
            props: {
                mode: 'rush',
                lesson: null,
                cards: [card('a', false), card('b', false)],
            },
            attachTo: document.body,
        });

        expect(wrapper.get('[role="timer"]').attributes('aria-label')).toBe(
            'Tempo restante: 5:00',
        );
        await expectAccessible(wrapper.element);

        await vi.advanceTimersByTimeAsync(61_000);
        expect(wrapper.get('[role="timer"]').attributes('aria-label')).toBe(
            'Tempo restante: 3:59',
        );

        await vi.advanceTimersByTimeAsync(240_000);
        expect(wrapper.text()).toContain('Corrida concluída');
    });

    it('explains when there is nothing due', () => {
        const wrapper = mount(Flashcards, {
            props: { mode: 'rush', lesson: null, cards: [] },
        });

        expect(wrapper.text()).toContain('Nenhuma revisão vencida agora');
    });
});

describe('Flashcards end of session', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.unstubAllGlobals();
    });

    it('shows the summary, XP and the next step without going home', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(
                async () =>
                    new Response(
                        JSON.stringify({
                            card: 'x',
                            interval_days: 2,
                            preview: [0, 1, 2, 4],
                        }),
                    ),
            ),
        );
        const wrapper = mount(Flashcards, {
            props: {
                mode: 'normal',
                lesson: { ulid: '01jaula', title: 'Aula' },
                cards: [card('a', true), card('b', true)],
            },
            attachTo: document.body,
        });

        for (const key of ['3', '4']) {
            await wrapper
                .get('[role="button"]')
                .trigger('keydown', { key: ' ' });
            window.dispatchEvent(new KeyboardEvent('keydown', { key }));
            await flushPromises();
        }

        const stats = wrapper.findAll('ul li').map((item) => item.text());

        expect(wrapper.text()).toContain('Flashcards em dia');
        expect(stats).toEqual(['0Errei', '0Difícil', '1Bom', '1Fácil']);
        expect(wrapper.text()).toContain('+14 XP');
        expect(wrapper.get('a[href="/aulas/01jaula"]').text()).toContain(
            'Próximo: rever a revisão da aula',
        );
        await expectAccessible(wrapper.element);
    });
});
