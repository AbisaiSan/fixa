import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import type { TodayPlan } from '@/types/plan';
import Today from './Today.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        Head: { render: () => null },
        usePage: () => ({
            props: {
                auth: { user: { name: 'Marina Souza' } },
                targetLang: { code: 'en' },
                aiLimit: null,
            },
            url: '/hoje',
        }),
    };
});

const plan = (overrides: Partial<TodayPlan> = {}): TodayPlan => ({
    date: '2026-10-26',
    goal: { minutes: 10, done: 3, planned: 10, percent: 30, counted: false },
    blocks: [
        {
            ulid: 'b1',
            kind: 'flashcards',
            activity: null,
            minutes: 3,
            count: 8,
            done: true,
            skipped: false,
            moved_from: null,
            href: null,
            lesson: null,
        },
        {
            ulid: 'b2',
            kind: 'lesson',
            activity: 'writing',
            minutes: 4,
            count: 6,
            done: false,
            skipped: false,
            moved_from: null,
            href: '/praticar/escrita/01s',
            lesson: {
                ulid: '01l',
                title: 'Present Perfect',
                lesson_date: '2026-10-21',
            },
        },
        {
            ulid: 'b3',
            kind: 'weak_point',
            activity: null,
            minutes: 3,
            count: 0,
            done: false,
            skipped: true,
            moved_from: null,
            href: null,
            lesson: null,
        },
    ],
    next: 'b2',
    streak: { current: 12, freezes: 1, max_freezes: 2 },
    xp: { total: 2740, level: 7 },
    rush: { due: 6, done: false },
    resume: null,
    ...overrides,
});

const latestLesson = {
    ulid: '01l',
    title: 'Present Perfect',
    lesson_date: '2026-10-21',
    status: 'ready' as const,
};

describe('Today', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('shows the goal ring, the 3 blocks and starts the next one in 1 click (PLA-06)', async () => {
        const wrapper = mount(Today, {
            props: { status: 'ready', latestLesson, plan: plan() },
            attachTo: document.body,
        });

        expect(wrapper.get('[role="img"]').attributes('aria-label')).toBe(
            'Meta do dia: 3 de 10 minutos',
        );
        expect(wrapper.text()).toContain('Faltam 7 minutos');
        expect(wrapper.text()).toContain('Próximo: escrita da aula.');
        expect(
            wrapper
                .findAll('a[href="/praticar/escrita/01s"]')
                .some((link) =>
                    link.text().includes('Escrita: Present Perfect'),
                ),
        ).toBe(true);
        expect(
            wrapper
                .findAll('a')
                .find((link) => link.text().includes('Continuar'))
                ?.attributes('href'),
        ).toBe('/praticar/escrita/01s');
        expect(wrapper.text()).toContain('Feito');
        expect(wrapper.text()).toContain('Nada para hoje');
        expect(wrapper.text()).toContain('12');
        expect(wrapper.text()).toContain('Modo corrida · 5 min');
        await expectAccessible(wrapper.element);
    });

    it('celebrates the goal with the streak and offers more practice', async () => {
        const wrapper = mount(Today, {
            props: {
                status: 'ready',
                latestLesson,
                plan: plan({
                    goal: {
                        minutes: 10,
                        done: 10,
                        planned: 10,
                        percent: 100,
                        counted: true,
                    },
                    next: null,
                    streak: { current: 13, freezes: 1, max_freezes: 2 },
                }),
            },
            attachTo: document.body,
        });

        expect(wrapper.text()).toContain('Meta cumprida!');
        expect(wrapper.text()).toContain(
            'Sua sequência agora é de 13 dias. Até amanhã!',
        );
        expect(wrapper.text()).toContain('Praticar mais um pouco');
        await expectAccessible(wrapper.element);
    });

    it('welcomes back without counting the missed days (GAM-05)', async () => {
        const wrapper = mount(Today, {
            props: {
                status: 'ready',
                latestLesson,
                plan: plan({
                    resume: 'welcome_back',
                    streak: { current: 0, freezes: 0, max_freezes: 2 },
                }),
            },
            attachTo: document.body,
        });

        expect(wrapper.text()).toContain('Bom te ver de volta.');
        expect(wrapper.text()).toContain('Sua sequência recomeça hoje');
        expect(wrapper.text()).not.toMatch(/perd|falt(ou|aram)/i);
        await expectAccessible(wrapper.element);
    });

    it('shows a moved task with the day it came from', () => {
        const base = plan();
        const wrapper = mount(Today, {
            props: {
                status: 'ready',
                latestLesson,
                plan: plan({
                    blocks: [
                        ...base.blocks,
                        {
                            ...base.blocks[1]!,
                            ulid: 'b4',
                            activity: 'reading',
                            moved_from: '2026-10-21',
                        },
                    ],
                }),
            },
        });

        expect(wrapper.text()).toContain('Remanejada de quarta');
    });
});
