import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import Progress from './Progress.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        Head: { render: () => null },
        usePage: () => ({
            props: { targetLang: { code: 'en' } },
            url: '/progresso',
        }),
    };
});

const days = ['26', '27', '28', '29', '30', '31'].map((n, index) => ({
    date: `2026-10-${n}`,
    studied: index < 3,
    status: index < 3 ? 'done' : index === 4 ? 'today' : 'planned',
}));

const props = {
    status: 'ready' as const,
    streak: { current: 12, longest: 21, freezes: 1, max_freezes: 2 },
    week: {
        days: [
            ...days,
            { date: '2026-11-01', studied: false, status: 'planned' },
        ],
        studied: 3,
        goal: 5,
    },
    level: { level: 7, xp: 2740, in_level: 340, to_next: 60, per_level: 400 },
    history: [
        { start: '2026-09-28', end: '2026-10-04', days: 5, minutes: 58 },
        { start: '2026-10-26', end: '2026-11-01', days: 1, minutes: 12 },
    ],
};

describe('Progress', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('shows streak, freezes, weekly goal, level bar and history without ranking (J5-F5)', async () => {
        const wrapper = mount(Progress, { props, attachTo: document.body });

        expect(wrapper.text()).toContain('12');
        expect(wrapper.text()).toContain('Seu recorde: 21 dias');
        expect(wrapper.text()).toContain('1 de 2 congelamentos');
        expect(wrapper.text()).toContain('3 de 5 dias');
        expect(wrapper.findAll('ol li')).toHaveLength(7);
        expect(wrapper.get('ol li').attributes('aria-label')).toBe(
            'Segunda: estudado',
        );
        expect(wrapper.text()).toContain('Nível 7');
        expect(
            wrapper.get('[role="progressbar"]').attributes('aria-valuenow'),
        ).toBe('340');
        expect(wrapper.text()).toContain('faltam 60 XP para o nível 8');
        expect(wrapper.text()).toContain('28/09–04/10');
        expect(wrapper.text()).toContain('5 dias · 58 min');
        expect(wrapper.text()).toContain('1 dia · 12 min');
        expect(wrapper.text()).toContain('Sem ranking');
        await expectAccessible(wrapper.element);
    });

    it('shows the friendly empty state before the first study', async () => {
        const wrapper = mount(Progress, {
            props: { status: 'empty' },
            attachTo: document.body,
        });

        expect(wrapper.text()).toContain('Seu progresso começa hoje');
        await expectAccessible(wrapper.element);
    });
});
