import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import GoalCelebration from './GoalCelebration.vue';

describe('GoalCelebration', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('announces the goal, the streak and the XP', async () => {
        const wrapper = mount(GoalCelebration, {
            props: { streak: 13, xpFrom: 2700, xpTo: 2740, reduceMotion: true },
            attachTo: document.body,
        });

        expect(wrapper.get('[role="status"]').text()).toContain(
            'Meta cumprida!',
        );
        expect(wrapper.text()).toContain('Sequência de 13 dias');
        expect(wrapper.text()).toContain('2740 XP no total');
        await expectAccessible(wrapper.element);
    });

    it('draws no confetti and jumps to the final XP when motion is reduced', () => {
        const wrapper = mount(GoalCelebration, {
            props: { streak: 1, xpFrom: 10, xpTo: 40, reduceMotion: true },
        });

        expect(
            wrapper.find('[aria-hidden="true"].pointer-events-none').exists(),
        ).toBe(false);
        expect(wrapper.text()).toContain('Sequência de 1 dia');
        expect(wrapper.text()).toContain('40 XP no total');
    });

    it('throws confetti and counts the XP up otherwise', () => {
        const wrapper = mount(GoalCelebration, {
            props: { streak: 2, xpFrom: 10, xpTo: 40, reduceMotion: false },
        });

        expect(wrapper.findAll('.pointer-events-none > span')).toHaveLength(18);
        expect(wrapper.text()).toContain('10 XP no total');
    });
});
