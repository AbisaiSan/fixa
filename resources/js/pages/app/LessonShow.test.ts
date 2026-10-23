import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import type { LessonDetail, LessonReview } from '@/types/lessons';
import LessonShow from './LessonShow.vue';

// O Head precisa do app do Inertia; aqui basta não renderizar nada.
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        Head: { render: () => null },
        usePage: () => ({
            props: { targetLang: { code: 'en' } },
            url: '/aulas',
        }),
    };
});

const lesson: LessonDetail = {
    ulid: '01jabc',
    title: 'Present Perfect no trabalho',
    lesson_date: '2026-10-21',
    source: 'pdf',
    pages: 4,
    language: 'en',
    level: 'b1',
    status: 'ready',
    failure_reason: null,
    word_count: 412,
    can_regenerate: true,
    can_retry: false,
    regenerations_left: 3,
};

const review: LessonReview = {
    ulid: '01jrev',
    summary: 'A aula tratou do Present Perfect. <script>alert(1)</script>',
    concepts: [
        {
            key: 'abc123defg',
            name: 'For e since',
            explanation: 'For indica a duração; since, o ponto de partida.',
            examples: [
                {
                    target: 'I have lived here for ten years.',
                    translation: 'Moro aqui há dez anos.',
                },
            ],
        },
    ],
    common_mistakes: [
        {
            wrong: 'I work here since 2022.',
            right: 'I have worked here since 2022.',
            why: 'Com since, a ação continua até agora.',
        },
    ],
    can_do: [
        'Consigo usar for e since.',
        'Consigo perguntar sobre experiências.',
    ],
    can_do_done: [1],
    reported: false,
    open_reports: [],
};

function render(overrides: Partial<LessonReview> = {}) {
    return mount(LessonShow, {
        props: { lesson, review: { ...review, ...overrides } },
        attachTo: document.body,
    });
}

describe('LessonShow', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('shows the review with origin, target language examples and the checklist', async () => {
        const wrapper = render();

        expect(wrapper.text()).toContain('Da aula de 21/10');
        expect(wrapper.text()).toContain('Inglês · B1');
        expect(wrapper.text()).toContain('PDF · 4 páginas');
        expect(wrapper.find('[lang="en"]').text()).toBe(
            'I have lived here for ten years.',
        );
        expect(wrapper.text()).toContain('1 de 2');
        expect(
            wrapper
                .findAll('input[type="checkbox"]')
                .map((box) => (box.element as HTMLInputElement).checked),
        ).toEqual([false, true]);
        await expectAccessible(wrapper.element);
    });

    it('never renders generated text as HTML (IAS-02)', () => {
        const wrapper = render();

        expect(wrapper.find('script').exists()).toBe(false);
        expect(wrapper.text()).toContain('<script>alert(1)</script>');
    });

    it('shows a reported item as under review with undo', async () => {
        const wrapper = render({
            reported: true,
            open_reports: [{ ulid: '01jrep', item: 'concept:abc123defg' }],
        });

        expect(wrapper.text()).toContain('Reportado · em análise');
        expect(wrapper.text()).not.toContain('Moro aqui há dez anos.');
        expect(wrapper.text()).toContain('Desfazer');
        await expectAccessible(wrapper.element);
    });
});

describe('LessonShow failures', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('shows the friendly failure with retry instead of the review', async () => {
        const wrapper = mount(LessonShow, {
            props: {
                lesson: {
                    ...lesson,
                    status: 'failed',
                    failure_reason: 'ai_unavailable',
                    can_regenerate: false,
                    can_retry: true,
                },
                review: null,
            },
            attachTo: document.body,
        });

        expect(wrapper.text()).toContain('A IA não respondeu agora.');
        expect(wrapper.text()).toContain('Tentar de novo');
        expect(wrapper.text()).not.toContain('Praticar esta aula');
        await expectAccessible(wrapper.element);
    });
});
