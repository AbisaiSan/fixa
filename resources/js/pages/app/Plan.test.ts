import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import Plan from './Plan.vue';

const patch = vi.fn();

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        Head: { render: () => null },
        router: { patch: (...args: unknown[]) => patch(...args) },
        usePage: () => ({
            props: { targetLang: { code: 'en' } },
            url: '/plano',
        }),
    };
});

const task = (
    ulid: string,
    kind: 'flashcards' | 'lesson' | 'weak_point',
    extra = {},
) => ({
    ulid,
    kind,
    activity: kind === 'lesson' ? ('reading' as const) : null,
    minutes: kind === 'lesson' ? 4 : 3,
    done: false,
    moved_from: null,
    lesson:
        kind === 'lesson'
            ? { title: 'Present Perfect', lesson_date: '2026-10-21' }
            : null,
    ...extra,
});

const props = () => ({
    today: '2026-10-30',
    weekStart: '2026-10-26',
    goal: 10,
    goalRange: { min: 5, max: 30, step: 5 },
    redone: true,
    days: [
        {
            date: '2026-10-26',
            status: 'done' as const,
            goal: 10,
            minutes: 10,
            tasks: [task('a', 'flashcards', { done: true })],
        },
        {
            date: '2026-10-27',
            status: 'done' as const,
            goal: 10,
            minutes: 10,
            tasks: [],
        },
        {
            date: '2026-10-28',
            status: 'frozen' as const,
            goal: 10,
            minutes: 10,
            tasks: [],
        },
        {
            date: '2026-10-29',
            status: 'missed' as const,
            goal: 10,
            minutes: 10,
            tasks: [],
        },
        {
            date: '2026-10-30',
            status: 'today' as const,
            goal: 10,
            minutes: 10,
            tasks: [
                task('b', 'flashcards'),
                task('c', 'lesson'),
                task('d', 'weak_point'),
            ],
        },
        {
            date: '2026-10-31',
            status: 'planned' as const,
            goal: 10,
            minutes: 14,
            tasks: [task('e', 'lesson', { moved_from: '2026-10-28' })],
        },
        {
            date: '2026-11-01',
            status: 'planned' as const,
            goal: 10,
            minutes: 10,
            tasks: [],
        },
    ],
});

describe('Plan', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        patch.mockReset();
    });

    it('shows the 7 days with a different icon per state and today selected (J5-F4)', async () => {
        const wrapper = mount(Plan, {
            props: props(),
            attachTo: document.body,
        });
        const days = wrapper.findAll('ol button');

        expect(days).toHaveLength(7);
        expect(days.map((day) => day.find('.icon').text())).toEqual([
            'check_circle',
            'check_circle',
            'ac_unit',
            'remove',
            'today',
            'radio_button_unchecked',
            'radio_button_unchecked',
        ]);
        expect(days[4]!.attributes('aria-pressed')).toBe('true');
        expect(days[5]!.attributes('aria-label')).toBe(
            'Sábado, 31: 14 minutos planejados',
        );
        expect(wrapper.text()).toContain(
            'Plano refeito para caber no seu ritmo',
        );
        expect(wrapper.text()).toContain('Sexta, 30 · hoje');
        expect(wrapper.text()).toContain('Leitura: Present Perfect');
        await expectAccessible(wrapper.element);
    });

    it('shows a moved task and a protected day without blame (GAM-05)', async () => {
        const wrapper = mount(Plan, {
            props: props(),
            attachTo: document.body,
        });

        await wrapper.findAll('ol button')[5]!.trigger('click');
        expect(wrapper.text()).toContain('Remanejada de quarta');
        expect(wrapper.text()).toContain('inclui remanejadas');

        await wrapper.findAll('ol button')[2]!.trigger('click');
        expect(wrapper.text()).toContain(
            'Sua sequência ficou protegida neste dia.',
        );

        await wrapper.findAll('ol button')[3]!.trigger('click');
        expect(wrapper.text()).toContain('Tudo bem');
        expect(wrapper.text()).not.toMatch(/perdeu|dívida/i);
    });

    it('saves a new daily goal between 5 and 30 minutes (PLA-01)', async () => {
        const wrapper = mount(Plan, { props: props() });
        const range = wrapper.get('input[type="range"]');

        expect(range.attributes('min')).toBe('5');
        expect(range.attributes('max')).toBe('30');
        await range.setValue('20');
        await range.trigger('change');

        expect(patch).toHaveBeenCalledWith(
            '/plano/meta',
            { daily_goal_minutes: 20 },
            expect.anything(),
        );
        expect(wrapper.text()).toContain('podem chegar a 30 min');
    });
});
