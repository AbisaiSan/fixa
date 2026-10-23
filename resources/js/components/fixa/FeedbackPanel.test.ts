import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import FeedbackPanel from './FeedbackPanel.vue';

const base = {
    xp: 6,
    answer: 'I work here since 2021.',
    corrected: 'I have worked here since 2021.',
    natural: "I've been working here since 2021.",
    explanation: 'Com since, use o Present Perfect.',
};

describe('FeedbackPanel', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('shows the result with icon and label, the error, the correction and XP', async () => {
        const wrapper = mount(FeedbackPanel, {
            props: { ...base, verdict: 'partial' },
            attachTo: document.body,
        });

        expect(wrapper.get('section').attributes('aria-live')).toBe('polite');
        expect(wrapper.text()).toContain('Quase lá · erro pequeno');
        expect(wrapper.text()).toContain('+6 XP');
        expect(wrapper.findAll('mark').map((mark) => mark.text())).toEqual([
            'work',
            'have',
            'worked',
        ]);
        expect(wrapper.text()).toContain('Guardamos no caderno de erros');
        await expectAccessible(wrapper.element);
    });

    it('a correct answer has no correction and no notebook note', async () => {
        const wrapper = mount(FeedbackPanel, {
            props: { ...base, verdict: 'total', answer: base.corrected },
            attachTo: document.body,
        });

        expect(wrapper.text()).toContain('Correto!');
        expect(wrapper.find('mark').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('Versão corrigida');
        expect(wrapper.text()).not.toContain('caderno de erros');
        await expectAccessible(wrapper.element);
    });

    it('never renders AI text as HTML (IAS-02)', () => {
        const wrapper = mount(FeedbackPanel, {
            props: {
                ...base,
                verdict: 'wrong',
                explanation: '<img src=x onerror=alert(1)> explicação',
                natural: '<script>alert(1)</script>',
                corrected: '<b>bold</b>',
            },
        });

        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.find('script').exists()).toBe(false);
        expect(wrapper.find('b').exists()).toBe(false);
        expect(wrapper.text()).toContain(
            '<img src=x onerror=alert(1)> explicação',
        );
    });

    it('emits report', async () => {
        const wrapper = mount(FeedbackPanel, {
            props: { ...base, verdict: 'wrong' },
        });

        await wrapper.get('button').trigger('click');

        expect(wrapper.emitted('report')).toHaveLength(1);
    });
});
