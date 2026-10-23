import { vi } from 'vite-plus/test';

// Props compartilhadas mínimas do Inertia para os componentes que usam usePage().
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        usePage: () => ({
            props: { targetLang: { code: 'en' } },
            url: '/hoje',
        }),
    };
});
