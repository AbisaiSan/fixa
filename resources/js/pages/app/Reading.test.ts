import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import Reading from './Reading.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        Head: { render: () => null },
        usePage: () => ({
            props: { targetLang: { code: 'en' } },
            url: '/praticar/leitura/x',
        }),
    };
});

function render() {
    return mount(Reading, {
        props: {
            session: {
                ulid: '01jread',
                title: "Ana's interview",
                paragraphs: [
                    'Last month, Ana applied for a job. She has <b>built</b> a portfolio.',
                ],
                lesson: { ulid: '01jaula', lesson_date: '2026-10-21' },
            },
            glossary: [
                {
                    form: 'applied',
                    term: 'to apply for',
                    part_of_speech: 'verbo',
                    translation: 'candidatar-se a',
                    known: false,
                },
                {
                    form: 'portfolio',
                    term: 'portfolio',
                    part_of_speech: 'substantivo',
                    translation: 'portfólio',
                    known: true,
                },
            ],
            questions: [
                {
                    ulid: '01jq',
                    position: 1,
                    type: 'choice',
                    context: null,
                    instruction: null,
                    sentence: 'What did Ana do?',
                    options: ['She applied for a job', 'She left'],
                    reported: false,
                    attempt: null,
                },
            ],
        },
        attachTo: document.body,
    });
}

describe('Reading', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.unstubAllGlobals();
    });

    it('marks glossary words as buttons and keeps the text as text', async () => {
        const wrapper = render();

        expect(
            wrapper.findAll('article button').map((button) => button.text()),
        ).toEqual(['applied', 'portfolio']);
        expect(wrapper.find('article b').exists()).toBe(false);
        expect(wrapper.get('article').text()).toContain('<b>built</b>');
        await expectAccessible(wrapper.element);
    });

    it('saves a word with one tap from the sheet', async () => {
        const fetch = vi.fn(
            async () =>
                new Response(
                    JSON.stringify({ word: '01jw', term: 'to apply for' }),
                ),
        );
        vi.stubGlobal('fetch', fetch);
        const wrapper = render();

        await wrapper.get('article button').trigger('click');
        await flushPromises();
        (
            Array.from(
                document.body.querySelectorAll('[role="dialog"] button'),
            ) as HTMLButtonElement[]
        )
            .find((button) =>
                button.textContent?.includes('Salvar no vocabulário'),
            )
            ?.click();
        await flushPromises();

        expect(fetch).toHaveBeenCalledOnce();
        expect(
            document.body.querySelector('[role="dialog"]')?.textContent,
        ).toContain('No seu vocabulário');
    });

    it('answers a question and shows the result', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(
                async () =>
                    new Response(
                        JSON.stringify({
                            verdict: 'total',
                            xp: 4,
                            correction: {
                                verdict: 'total',
                                corrected: 'She applied for a job',
                                natural: '',
                                explanation: '',
                                error: null,
                                category: null,
                                point: null,
                                source: 'key',
                            },
                        }),
                    ),
            ),
        );
        const wrapper = render();

        await wrapper
            .get('input[value="She applied for a job"]')
            .setValue(true);
        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'Verificar')
            ?.trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('Isso mesmo!');
        expect(wrapper.text()).toContain('1 de 1 certas');
    });
});
