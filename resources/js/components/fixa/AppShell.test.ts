import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { isActive } from '@/lib/navigation';
import { expectAccessible } from '@/test/axe';
import AppShell from './AppShell.vue';

describe('AppShell', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('renders both navigations with the current page marked', async () => {
        const wrapper = mount(AppShell, {
            slots: { default: '<h1>Hoje</h1>' },
            attachTo: document.body,
        });

        const navs = wrapper.findAll('nav');
        const current = wrapper.findAll('[aria-current="page"]');

        expect(navs).toHaveLength(2);
        // Texto acessível: o nome do ícone fica fora, por estar em aria-hidden.
        const accessibleText = (element: Element) =>
            [...element.childNodes]
                .filter(
                    (node) =>
                        !(
                            node instanceof Element &&
                            node.closest('[aria-hidden="true"]')
                        ),
                )
                .map((node) => node.textContent?.trim())
                .join('');

        expect(current.map((link) => accessibleText(link.element))).toEqual([
            'Hoje',
            'Hoje',
        ]);
        expect(wrapper.get('main#conteudo').text()).toBe('Hoje');
        expect(wrapper.get('a[href="#conteudo"]').text()).toBe(
            'Pular para o conteúdo',
        );
        // Por CSS só uma navegação existe por largura de tela; o jsdom não aplica media queries,
        // então cada combinação é verificada como o navegador a exporia.
        const [desktop, mobile] = wrapper
            .findAll('nav')
            .map((nav) => nav.element.parentElement as HTMLElement);

        for (const hidden of [mobile, desktop]) {
            hidden.style.display = 'none';
            await expectAccessible(wrapper.element);
            hidden.style.display = '';
        }
    });

    it('hides navigation in focus mode', () => {
        const wrapper = mount(AppShell, {
            props: { focus: true },
            slots: { default: 'Sessão' },
        });

        expect(wrapper.findAll('nav')).toHaveLength(0);
        expect(wrapper.get('main').text()).toBe('Sessão');
    });
});

describe('isActive', () => {
    it('matches the section and its children only', () => {
        expect(isActive('/aulas', '/aulas')).toBe(true);
        expect(isActive('/aulas/01abc?tab=1', '/aulas')).toBe(true);
        expect(isActive('/aulasx', '/aulas')).toBe(false);
        expect(isActive('/hoje', '/aulas')).toBe(false);
    });
});
