import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import Index from './Index.vue';

const patch = vi.fn();

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        Head: { render: () => null },
        router: { patch: (...args: unknown[]) => patch(...args) },
        usePage: () => ({
            props: {
                auth: {
                    user: {
                        name: 'Marina Souza',
                        email: 'marina@example.test',
                    },
                },
                targetLang: { code: 'en' },
            },
            url: '/configuracoes',
        }),
    };
});

const props = (reminder: boolean) => ({
    study: {
        daily_goal_minutes: 10,
        target_language: 'en',
        timezone: 'America/Sao_Paulo',
        level: null,
    },
    goalRange: { min: 5, max: 30, step: 5 },
    languages: { available: ['en'], upcoming: ['es'] },
    timezones: ['America/Sao_Paulo'],
    reminderHours: [6, 7, 19, 22],
    preferences: {
        theme: 'system' as const,
        notify_daily_reminder: reminder,
        notify_weekly_summary: false,
        notify_streak: false,
        daily_reminder_hour: 19,
    },
    twoFactor: { enabled: false, requiresConfirmation: true, required: false },
    passwordRules: 'required',
});

describe('Settings daily reminder (J5-F6)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        patch.mockReset();
    });

    it('offers the hour only when the reminder is on', () => {
        expect(
            mount(Index, { props: props(false) })
                .find('#settings-reminder-hour')
                .exists(),
        ).toBe(false);

        const wrapper = mount(Index, { props: props(true) });

        expect(wrapper.text()).toContain('Às 19h, se você ainda não estudou');
        expect(
            wrapper
                .findAll('#settings-reminder-hour option')
                .map((option) => option.text()),
        ).toEqual(['6h', '7h', '19h', '22h']);
    });

    it('saves the chosen hour with the other preferences', async () => {
        const wrapper = mount(Index, {
            props: props(true),
            attachTo: document.body,
        });

        await wrapper.get('#settings-reminder-hour').setValue('7');

        expect(patch).toHaveBeenCalledWith(
            '/configuracoes/preferencias',
            expect.objectContaining({
                notify_daily_reminder: true,
                daily_reminder_hour: 7,
            }),
            expect.anything(),
        );
        expect(wrapper.text()).toContain('Às 7h');
        await expectAccessible(
            wrapper.get('[aria-labelledby="settings-notifications"]').element,
        );
    });
});
