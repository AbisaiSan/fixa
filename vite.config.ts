import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { fontsource, local } from 'laravel-vite-plugin/fonts';
import { fileURLToPath } from 'node:url';
import { defineConfig, lazyPlugins } from 'vite-plus';

const vuePlugin = () =>
    vue({
        template: {
            transformAssetUrls: {
                base: null,
                includeAbsolute: false,
            },
        },
    });

// Nos testes (Vitest) só o Vue é necessário. O plugin do Laravel criaria
// public/hot e faria o app apontar para um servidor Vite que não existe.
const isTest = process.env.VITEST !== undefined;

export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    plugins: lazyPlugins(() =>
        isTest
            ? [vuePlugin()]
            : [
                  laravel({
                      input: ['resources/css/app.css', 'resources/js/app.ts'],
                      refresh: true,
                      // Fontes servidas pelo próprio app, sem CDN (J0-F3, OPE-01, DAD-01).
                      fonts: [
                          fontsource('Plus Jakarta Sans', {
                              alias: 'ui',
                              weights: [400, 500, 600, 700, 800],
                              preload: [{ weight: 400 }, { weight: 800 }],
                          }),
                          fontsource('Gloock', { alias: 'display' }),
                          fontsource('Source Serif 4', {
                              alias: 'target',
                              weights: [400, 600],
                              preload: false,
                          }),
                          local('Material Symbols Rounded', {
                              alias: 'icons',
                              display: 'block',
                              preload: false,
                              variants: [
                                  {
                                      src: 'node_modules/@fontsource-variable/material-symbols-rounded/files/material-symbols-rounded-latin-fill-normal.woff2',
                                      weight: '400',
                                  },
                              ],
                          }),
                      ],
                  }),
                  inertia(),
                  tailwindcss(),
                  vuePlugin(),
                  wayfinder({
                      formVariants: true,
                  }),
              ],
    ),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.test.ts'],
        setupFiles: ['resources/js/test/setup.ts'],
    },
    lint: {
        ignorePatterns: [
            'docs/**',
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'docs/**',
            // Prompts versionados ficam exatamente como foram testados.
            'resources/prompts/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
