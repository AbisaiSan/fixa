import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import LessonFailure from './LessonFailure.vue';

const codes = [
    'too_short',
    'pdf_without_text',
    'virus_found',
    'unreadable_file',
    'ai_invalid_output',
    'ai_unavailable',
    'ai_budget_reached',
];

describe('LessonFailure', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it.each(codes)('has a friendly message for %s', async (reason) => {
        const wrapper = mount(LessonFailure, {
            props: { reason, canRetry: true },
            attachTo: document.body,
        });

        expect(wrapper.get('[role="alert"]').text()).not.toContain(reason);
        expect(
            wrapper.get('[role="alert"] strong').text().length,
        ).toBeGreaterThan(10);
        await expectAccessible(wrapper.element);
    });

    it('offers retry only when another attempt can help', async () => {
        const aiDown = mount(LessonFailure, {
            props: { reason: 'ai_unavailable', canRetry: true },
        });
        const tooShort = mount(LessonFailure, {
            props: { reason: 'too_short', canRetry: false },
        });

        expect(aiDown.text()).toContain('Tentar de novo');
        expect(tooShort.text()).not.toContain('Tentar de novo');
        expect(tooShort.text()).toContain('Enviar de novo');

        await aiDown.findAll('button')[0].trigger('click');
        expect(aiDown.emitted('retry')).toHaveLength(1);
    });

    it('falls back to a generic message for an unknown code', () => {
        const wrapper = mount(LessonFailure, {
            props: { reason: 'something_new', canRetry: true },
        });

        expect(wrapper.text()).toContain('Algo deu errado ao preparar a aula.');
    });
});
