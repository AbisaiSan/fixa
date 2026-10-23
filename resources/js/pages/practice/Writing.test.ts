import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import type { PracticeItem } from '@/types/practice';
import Writing from './Writing.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        Head: { render: () => null },
        usePage: () => ({
            props: { targetLang: { code: 'en' }, aiLimit: { limit: 30 } },
            url: '/praticar/escrita/x',
        }),
    };
});

const item = (
    ulid: string,
    type: PracticeItem['type'],
    sentence: string,
    extra: Partial<PracticeItem> = {},
): PracticeItem => ({
    ulid,
    position: 1,
    type,
    context: 'work',
    instruction: 'Use o Present Perfect.',
    sentence,
    options: null,
    reported: false,
    attempt: null,
    ...extra,
});

const correction = (verdict: 'total' | 'partial' | 'wrong', xp: number) => ({
    verdict,
    xp,
    correction: {
        verdict,
        error: null,
        category: null,
        explanation: 'Com since, use o Present Perfect.',
        corrected: 'I have worked here since 2021.',
        natural: "I've been working here since 2021.",
        point: null,
        source: 'key',
    },
});

function render(items: PracticeItem[]) {
    return mount(Writing, {
        props: {
            session: {
                ulid: '01jses',
                kind: 'writing',
                lesson: {
                    ulid: '01jaula',
                    title: 'Aula',
                    lesson_date: '2026-10-21',
                },
            },
            items,
        },
        attachTo: document.body,
    });
}

describe('Writing session', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.unstubAllGlobals();
    });

    it('a whole session works with the keyboard only', async () => {
        const fetch = vi
            .fn()
            .mockResolvedValueOnce(
                new Response(JSON.stringify(correction('total', 10))),
            )
            .mockResolvedValueOnce(
                new Response(JSON.stringify(correction('wrong', 2))),
            );
        vi.stubGlobal('fetch', fetch);
        const wrapper = render([
            item('a', 'fill', 'I ___ (work) here since 2021.'),
            item('b', 'translate', 'Eu trabalho aqui desde 2021.'),
        ]);
        await flushPromises();

        expect(document.activeElement?.id).toBe('writing-answer');
        expect(wrapper.text()).toContain('1 de 2');
        expect(wrapper.text()).toContain('Da aula de 21/10');
        await expectAccessible(wrapper.element);

        const input = wrapper.get('#writing-answer');
        await input.setValue('have worked');
        await input.trigger('keydown', { key: 'Enter' });
        await flushPromises();

        expect(wrapper.text()).toContain('Correto!');
        expect(document.activeElement?.textContent).toContain('Continuar');

        (document.activeElement as HTMLButtonElement).click();
        await flushPromises();

        expect(wrapper.text()).toContain('2 de 2');
        expect(document.activeElement?.id).toBe('writing-answer');

        const textarea = wrapper.get('#writing-answer');
        await textarea.setValue('I work here since 2021.');
        await textarea.trigger('keydown', { key: 'Enter', ctrlKey: true });
        await flushPromises();

        expect(wrapper.text()).toContain('Ainda não');
        expect(wrapper.findAll('mark').length).toBeGreaterThan(0);
        await expectAccessible(wrapper.element);

        (document.activeElement as HTMLButtonElement).click();
        await flushPromises();

        expect(wrapper.text()).toContain('Escrita concluída');
        expect(wrapper.text()).toContain('+12 XP');
    });

    it('shows the limit screen when the AI corrections ran out', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(
                async () =>
                    new Response(
                        JSON.stringify({ error: 'ai_limit_reached' }),
                        { status: 429 },
                    ),
            ),
        );
        const wrapper = render([item('a', 'context', 'Conte uma conquista.')]);

        await wrapper
            .get('#writing-answer')
            .setValue('My biggest achievement...');
        await wrapper
            .get('button.w-full, button[type="button"]:not([aria-label])')
            .trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain(
            'Você usou as 30 correções de IA de hoje',
        );
        expect(wrapper.find('a[href="/praticar/flashcards"]').exists()).toBe(
            true,
        );
    });

    it('keeps the answer when the connection drops', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => Promise.reject(new TypeError('offline'))),
        );
        const wrapper = render([item('a', 'fill', 'I ___ (work) here.')]);

        const input = wrapper.get('#writing-answer');
        await input.setValue('have worked');
        await input.trigger('keydown', { key: 'Enter' });
        await flushPromises();

        expect(wrapper.text()).toContain('Sem conexão');
        expect(
            (wrapper.get('#writing-answer').element as HTMLInputElement).value,
        ).toBe('have worked');
    });

    it('resumes after the answered items', () => {
        const wrapper = render([
            item('a', 'fill', 'I ___ here.', {
                attempt: {
                    ...correction('total', 10),
                    answer: 'have worked',
                } as PracticeItem['attempt'],
            }),
            item('b', 'translate', 'Eu trabalho aqui.'),
        ]);

        expect(wrapper.text()).toContain('2 de 2');
    });
});
