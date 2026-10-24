import { flushPromises, mount } from '@vue/test-utils';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import NextBlockActions from './NextBlockActions.vue';

const summary = (counted: boolean) => ({
    date: '2026-10-26',
    next: null,
    goal: { minutes: 10, done: 10, planned: 10, percent: 100, counted },
    streak: { current: 4 },
    xp: { total: 120, level: 1 },
});

describe('NextBlockActions', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.unstubAllGlobals();
    });

    it('celebrates the goal once a day and goes back to Today when nothing is left (J5-F3)', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => new Response(JSON.stringify(summary(true)))),
        );
        const first = mount(NextBlockActions, {
            props: { current: 'writing', gained: 20 },
            attachTo: document.body,
        });
        await flushPromises();

        expect(first.text()).toContain('Meta cumprida!');
        expect(first.text()).toContain('Sequência de 4 dias');
        expect(first.get('a[href="/hoje"]').text()).toContain(
            'Voltar para Hoje',
        );
        await expectAccessible(first.element);

        const second = mount(NextBlockActions, {
            props: { current: 'flashcards' },
        });
        await flushPromises();

        expect(second.text()).not.toContain('Meta cumprida!');
    });

    it('does not celebrate before the goal is met', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => new Response(JSON.stringify(summary(false)))),
        );
        const wrapper = mount(NextBlockActions, {
            props: { current: 'writing' },
        });
        await flushPromises();

        expect(wrapper.text()).not.toContain('Meta cumprida!');
    });

    it('keeps the way back when offline', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => Promise.reject(new TypeError('offline'))),
        );
        const wrapper = mount(NextBlockActions, {
            props: {
                current: 'reading',
                fallbackHref: '/praticar',
                fallbackLabel: 'Voltar para Praticar',
            },
        });
        await flushPromises();

        expect(wrapper.get('a[href="/praticar"]').text()).toContain(
            'Voltar para Praticar',
        );
    });
});
