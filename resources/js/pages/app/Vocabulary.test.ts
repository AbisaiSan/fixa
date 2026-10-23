import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { expectAccessible } from '@/test/axe';
import type { WordDetail, WordListItem } from '@/types/lessons';
import Vocabulary from './Vocabulary.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        Head: { render: () => null },
        usePage: () => ({
            props: { targetLang: { code: 'en' } },
            url: '/vocabulario',
        }),
    };
});

const words: WordListItem[] = [
    {
        ulid: '01jw1',
        term: 'deadline',
        part_of_speech: 'substantivo',
        translation: 'prazo final',
        state: 'learning',
    },
    {
        ulid: '01jw2',
        term: 'strength',
        part_of_speech: 'substantivo',
        translation: 'ponto forte',
        state: 'mastered',
    },
];

const selected: WordDetail = {
    ...words[0],
    definition: 'A data ou hora em que algo precisa estar pronto.',
    examples: [
        {
            target: "We've never missed a deadline.",
            translation: 'Nunca perdemos um prazo.',
        },
    ],
    collocations: ['meet a deadline'],
    origin: 'lesson',
    lesson: { ulid: '01jaula', lesson_date: '2026-10-21' },
    generated: true,
    report: null,
};

function render(extra: Record<string, unknown> = {}) {
    return mount(Vocabulary, {
        props: {
            status: 'ready',
            filters: { q: '', estado: '', aula: '', origem: '' },
            counts: { all: 2, new: 0, learning: 1, mastered: 1 },
            words,
            lessons: [
                { ulid: '01jaula', title: 'Aula', lesson_date: '2026-10-21' },
            ],
            selected: null,
            aiLeft: 30,
            ...extra,
        },
        attachTo: document.body,
    });
}

describe('Vocabulary', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('lists the words with state chips and filters', async () => {
        const wrapper = render();

        expect(wrapper.findAll('ul li a')).toHaveLength(2);
        expect(wrapper.text()).toContain('Todas · 2');
        expect(wrapper.text()).toContain('Dominadas · 1');
        expect(wrapper.get('[aria-pressed="true"]').text()).toContain('Todas');
        expect(wrapper.text()).toContain('2 palavras');
        expect(wrapper.find('[lang="en"]').text()).toBe('deadline');
        await expectAccessible(wrapper.element);
    });

    it('shows the word detail with origin, examples and actions', async () => {
        const wrapper = render({ selected });
        const article = wrapper.get('article');

        expect(article.text()).toContain('Da aula de 21/10');
        expect(article.text()).toContain('Nunca perdemos um prazo.');
        expect(article.text()).toContain('Reportar erro');
        expect(
            article.get('a[href="/praticar/flashcards?aula=01jaula"]').text(),
        ).toContain('Revisar agora');
        expect(wrapper.get('a[aria-current="true"]').text()).toContain(
            'deadline',
        );
        await expectAccessible(wrapper.element);
    });

    it('shows a reported word as under review', () => {
        const wrapper = render({ selected: { ...selected, report: '01jrep' } });

        expect(wrapper.text()).toContain('Reportado · em análise.');
        expect(wrapper.text()).toContain('Desfazer');
    });

    it('offers to clear filters when nothing matches', () => {
        const wrapper = render({
            words: [],
            filters: { q: 'zzz', estado: '', aula: '', origem: '' },
        });

        expect(wrapper.text()).toContain(
            'Nenhuma palavra encontrada com esses filtros.',
        );
        expect(wrapper.text()).toContain('Limpar filtros');
    });
});
