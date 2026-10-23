import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import Flashcard from './Flashcard.vue';
import RateBar from './RateBar.vue';
import SessionHeader from './SessionHeader.vue';

describe('flashcard components', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('Flashcard flips with Space and Enter and announces the back', async () => {
        const wrapper = mount(Flashcard, {
            props: {
                front: 'deadline',
                back: 'prazo final',
                example: "We've never missed a deadline.",
                exampleTranslation: 'Nunca perdemos um prazo.',
                partOfSpeech: 'substantivo',
                flipped: false,
                'onUpdate:flipped': (value: boolean) =>
                    wrapper.setProps({ flipped: value }),
            },
            attachTo: document.body,
        });
        const card = wrapper.get('[role="button"]');

        expect(card.attributes('aria-label')).toContain(
            'Frente do cartão: deadline',
        );
        expect(wrapper.find('[lang="en"]').text()).toBe('deadline');
        await expectAccessible(wrapper.element);

        await card.trigger('keydown', { key: ' ' });

        expect(card.attributes('aria-label')).toContain(
            'Verso do cartão: prazo final',
        );
        expect(wrapper.get('[aria-live="polite"]').text()).toContain(
            "prazo final. We've never missed a deadline.",
        );
        await expectAccessible(wrapper.element);

        await card.trigger('keydown', { key: 'Enter' });

        expect(card.attributes('aria-label')).toContain('Frente do cartão');
    });

    it('RateBar shows the four ratings with their intervals and shortcuts', async () => {
        const wrapper = mount(RateBar, {
            props: { preview: [0, 1, 6, 35] },
            attachTo: document.body,
        });
        const buttons = wrapper.findAll('button');

        expect(buttons.map((button) => button.text())).toEqual([
            'Errei<1 min',
            'Difícil1 dia',
            'Bom6 dias',
            'Fácil1 mês',
        ]);
        expect(buttons[2].attributes('aria-keyshortcuts')).toBe('3');

        await buttons[3].trigger('click');

        expect(wrapper.emitted('rate')).toEqual([[4]]);
        await expectAccessible(wrapper.element);
    });

    it('SessionHeader shows the progress and counter', async () => {
        const wrapper = mount(SessionHeader, {
            props: { current: 2, total: 8 },
            attachTo: document.body,
        });
        const bar = wrapper.get('[role="progressbar"]');

        expect(bar.attributes('aria-valuenow')).toBe('2');
        expect(bar.attributes('aria-valuemax')).toBe('8');
        expect(wrapper.text()).toContain('3 de 8');

        await wrapper.get('button').trigger('click');

        expect(wrapper.emitted('exit')).toHaveLength(1);
        await expectAccessible(wrapper.element);
    });
});

describe('Flashcard swipe', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    const pointer = (type: string, clientX: number) =>
        new PointerEvent(type, { clientX, pointerId: 1, bubbles: true });

    it('swiping the back right or left emits the direction, and short drags do nothing', async () => {
        const wrapper = mount(Flashcard, {
            props: { front: 'deadline', back: 'prazo final', flipped: true },
        });
        const card = wrapper.get('[role="button"]').element;

        card.dispatchEvent(pointer('pointerdown', 100));
        card.dispatchEvent(pointer('pointermove', 260));
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Bom →');
        card.dispatchEvent(pointer('pointerup', 260));

        card.dispatchEvent(pointer('pointerdown', 300));
        card.dispatchEvent(pointer('pointermove', 150));
        card.dispatchEvent(pointer('pointerup', 150));

        card.dispatchEvent(pointer('pointerdown', 100));
        card.dispatchEvent(pointer('pointermove', 150));
        card.dispatchEvent(pointer('pointerup', 150));

        expect(wrapper.emitted('swipe')).toEqual([['right'], ['left']]);
    });

    it('the front does not swipe', () => {
        const wrapper = mount(Flashcard, {
            props: { front: 'deadline', back: 'prazo final', flipped: false },
        });
        const card = wrapper.get('[role="button"]').element;

        card.dispatchEvent(pointer('pointerdown', 100));
        card.dispatchEvent(pointer('pointermove', 400));
        card.dispatchEvent(pointer('pointerup', 400));

        expect(wrapper.emitted('swipe')).toBeUndefined();
    });
});
