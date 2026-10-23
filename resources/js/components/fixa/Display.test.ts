import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import Banner from './Banner.vue';
import Card from './Card.vue';
import EmptyState from './EmptyState.vue';
import OriginTag from './OriginTag.vue';
import Skeleton from './Skeleton.vue';
import StatusChip from './StatusChip.vue';
import type { StatusKind } from './StatusChip.vue';
import Toast from './Toast.vue';

const kinds: StatusKind[] = [
    'nova',
    'aprendendo',
    'dominada',
    'feito',
    'hoje',
    'protegido',
    'perdido',
    'total',
    'parcial',
    'erro',
];

describe('display components', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it.each(kinds)(
        'StatusChip %s shows text, never color alone',
        async (kind) => {
            const wrapper = mount(StatusChip, {
                props: { kind },
                attachTo: document.body,
            });

            expect(wrapper.text().length).toBeGreaterThan(2);
            expect(wrapper.get('.icon').attributes('aria-hidden')).toBe('true');
            await expectAccessible(wrapper.element);
        },
    );

    it('OriginTag tells where the item came from', () => {
        expect(
            mount(OriginTag, { props: { lessonDate: '21/10' } }).text(),
        ).toContain('Da aula de 21/10');
        expect(mount(OriginTag).text()).toContain('Adicionada por mim');
    });

    it('Card supports the hero tone', () => {
        const wrapper = mount(Card, {
            props: { tone: 'hero', as: 'section' },
            slots: { default: 'Meta' },
        });

        expect(wrapper.element.tagName).toBe('SECTION');
        expect(wrapper.classes()).toContain('bg-(image:--hero)');
    });

    it('Toast is announced as status', async () => {
        const wrapper = mount(Toast, {
            slots: { default: 'Revisão pronta.' },
            attachTo: document.body,
        });

        expect(wrapper.attributes('role')).toBe('status');
        await expectAccessible(wrapper.element);
    });

    it('Banner errors are announced as alerts', async () => {
        const wrapper = mount(Banner, {
            props: {
                tone: 'error',
                icon: 'error',
                title: 'Não conseguimos enviar.',
            },
            slots: { default: 'O que você preencheu continua aqui.' },
            attachTo: document.body,
        });

        expect(wrapper.attributes('role')).toBe('alert');
        expect(wrapper.get('strong').text()).toBe('Não conseguimos enviar.');
        await expectAccessible(wrapper.element);
    });

    it('Skeleton is busy and has a status text', async () => {
        const wrapper = mount(Skeleton, {
            props: { label: 'Gerando revisão', lines: 4 },
            attachTo: document.body,
        });

        expect(wrapper.attributes('aria-busy')).toBe('true');
        expect(wrapper.text()).toContain('Gerando revisão');
        expect(wrapper.findAll('[aria-hidden="true"]')).toHaveLength(4);
        await expectAccessible(wrapper.element);
    });

    it('EmptyState hides the illustration from assistive technology', async () => {
        const wrapper = mount(EmptyState, {
            props: { title: 'Tudo em dia por hoje' },
            slots: { default: 'Volte amanhã.' },
            attachTo: document.body,
        });

        expect(wrapper.text()).toContain('Tudo em dia por hoje');
        await expectAccessible(wrapper.element);
    });
});
