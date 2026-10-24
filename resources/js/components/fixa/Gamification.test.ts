import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import FreezeSlots from './FreezeSlots.vue';
import ProgressRing from './ProgressRing.vue';
import StreakChip from './StreakChip.vue';

describe('gamification components', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('ProgressRing names the progress and clamps the arc', async () => {
        const wrapper = mount(ProgressRing, {
            props: { percent: 140, label: 'Meta do dia: 14 de 10 minutos' },
            slots: { default: '<span>14</span>' },
            attachTo: document.body,
        });

        expect(wrapper.get('[role="img"]').attributes('aria-label')).toBe(
            'Meta do dia: 14 de 10 minutos',
        );
        expect(
            wrapper.findAll('circle')[1]?.attributes('stroke-dashoffset'),
        ).toBe('0');
        await expectAccessible(wrapper.element);
    });

    it('ProgressRing starts empty at zero', () => {
        const wrapper = mount(ProgressRing, {
            props: { percent: 0, label: 'Meta do dia: 0 de 10 minutos' },
        });
        const arc = wrapper.findAll('circle')[1];

        expect(arc?.attributes('stroke-dashoffset')).toBe(
            arc?.attributes('stroke-dasharray'),
        );
    });

    it.each([
        [0, 'Sua sequência recomeça hoje'],
        [1, 'dia de sequência'],
        [12, 'dias de sequência'],
    ])('StreakChip with %i days says it in words', async (days, text) => {
        const wrapper = mount(StreakChip, {
            props: { days },
            attachTo: document.body,
        });

        expect(wrapper.text()).toContain(text);
        expect(wrapper.get('a').attributes('aria-label')).toBeTruthy();
        await expectAccessible(wrapper.element);
    });

    it.each([
        [0, 'Um novo chega na segunda'],
        [1, 'congelamento disponível'],
        [2, 'congelamentos disponíveis'],
    ])('FreezeSlots with %i shows the balance of 2', async (count, text) => {
        const wrapper = mount(FreezeSlots, {
            props: { count },
            attachTo: document.body,
        });

        expect(wrapper.text()).toContain(text);
        expect(wrapper.text()).toContain('de 2');
        expect(
            wrapper
                .findAll('.icon')
                .filter((icon) => icon.text() === 'ac_unit'),
        ).toHaveLength(count);
        await expectAccessible(wrapper.element);
    });
});
