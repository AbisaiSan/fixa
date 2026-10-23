import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import Mistakes from './Mistakes.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        Head: { render: () => null },
        usePage: () => ({
            props: { targetLang: { code: 'en' } },
            url: '/praticar/caderno-de-erros',
        }),
    };
});

describe('Mistakes', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('shows each point with category, priority, cycle and streak', async () => {
        const wrapper = mount(Mistakes, {
            props: {
                filter: null,
                counts: {
                    all: 1,
                    grammar: 0,
                    vocabulary: 0,
                    preposition: 1,
                    spelling: 0,
                },
                mistakes: [
                    {
                        ulid: '01jm',
                        category: 'preposition',
                        priority: true,
                        repetitions: 3,
                        answer: "I've lived here since three years.",
                        corrected: "I've lived here for three years.",
                        explanation: 'Duração pede for.',
                        step: 1,
                        correct_streak: 2,
                        due_in_days: 1,
                        lesson_date: '2026-10-21',
                    },
                ],
            },
            attachTo: document.body,
        });

        expect(wrapper.text()).toContain('Preposição');
        expect(wrapper.text()).toContain('Prioridade');
        expect(wrapper.text()).toContain('Repetido 3×');
        expect(wrapper.text()).toContain('Próxima revisão: amanhã');
        expect(wrapper.get('[aria-current="step"]').text()).toBe('3 dias');
        expect(wrapper.text()).toContain('2 de 3 acertos seguidos para sair');
        expect(wrapper.text()).toContain('Todas · 1');
        await expectAccessible(wrapper.element);
    });

    it('shows the empty notebook', () => {
        const wrapper = mount(Mistakes, {
            props: {
                filter: null,
                counts: {
                    all: 0,
                    grammar: 0,
                    vocabulary: 0,
                    preposition: 0,
                    spelling: 0,
                },
                mistakes: [],
            },
        });

        expect(wrapper.text()).toContain('Seu caderno de erros está vazio');
    });
});
