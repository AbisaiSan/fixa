import type { StringKey } from '@/i18n';

export type NavItem = { href: string; icon: string; label: StringKey };

/**
 * Destinos principais do protótipo, na ordem da BottomNav.
 */
export const mainNav: NavItem[] = [
    { href: '/hoje', icon: 'today', label: 'nav.today' },
    { href: '/aulas', icon: 'menu_book', label: 'nav.lessons' },
    { href: '/praticar', icon: 'edit_note', label: 'nav.practice' },
    { href: '/vocabulario', icon: 'translate', label: 'nav.vocabulary' },
    { href: '/progresso', icon: 'insights', label: 'nav.progress' },
];

export function isActive(currentUrl: string, href: string): boolean {
    const path = currentUrl.split('?')[0];

    return path === href || path.startsWith(`${href}/`);
}
