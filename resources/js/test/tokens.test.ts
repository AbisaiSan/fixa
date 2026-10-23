/// <reference types="node" />
import { describe, expect, it } from 'vite-plus/test';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

/**
 * Contraste WCAG AA dos pares de tokens usados em texto (J0-F8).
 * O axe no jsdom não calcula cores, então a garantia fica aqui.
 */
const tokensCss = readFileSync(
    resolve(process.cwd(), 'resources/css/tokens.css'),
    'utf8',
);

function block(selector: string): Record<string, string> {
    const start = tokensCss.indexOf(`${selector} {`);
    const body = tokensCss.slice(start, tokensCss.indexOf('}', start));

    return Object.fromEntries(
        [...body.matchAll(/--([a-z0-9]+):\s*(#[0-9a-f]{6});/gi)].map(
            (match) => [match[1], match[2]],
        ),
    );
}

function luminance(hex: string): number {
    const [r, g, b] = [1, 3, 5].map((index) => {
        const channel = parseInt(hex.slice(index, index + 2), 16) / 255;

        return channel <= 0.04045
            ? channel / 12.92
            : ((channel + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function contrast(a: string, b: string): number {
    const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);

    return (light + 0.05) / (dark + 0.05);
}

const light = block("[data-theme='light']");
const dark = { ...light, ...block("[data-theme='dark']") };

// Texto sobre fundo: mínimo 4,5:1.
const textPairs: [string, string][] = [
    ['ink', 'bg'],
    ['ink', 'sf'],
    ['ink', 'sf2'],
    ['ink2', 'bg'],
    ['ink2', 'sf'],
    ['onpri', 'prid'],
    ['prid', 'sf'],
    ['prid', 'prit'],
    ['ambd', 'ambt'],
    ['okd', 'okt'],
    ['errd', 'errt'],
    ['infod', 'infot'],
    ['errd', 'sf'],
    ['onpri', 'errd'],
    // Revisão da aula (J2-F4): exemplos, erros comuns e item em análise.
    ['ink2', 'sf2'],
    ['errd', 'sf2'],
    ['okd', 'sf2'],
    ['okd', 'sf'],
    ['ink', 'infot'],
    ['ink2', 'infot'],
    ['ink', 'prit'],
    // Flashcards (J3-F1): botões de nota e selos.
    ['ink', 'errt'],
    ['ink', 'ambt'],
    ['ink', 'okt'],
    ['ambd', 'ambt'],
];

// Anel de foco e elementos gráficos: mínimo 3:1.
const graphicPairs: [string, string][] = [
    ['focus', 'sf'],
    ['focus', 'bg'],
    ['pri', 'sf'],
];

describe.each([
    ['claro', light],
    ['escuro', dark],
])('tokens do tema %s', (_name, theme) => {
    it.each(textPairs)('%s sobre %s tem contraste AA para texto', (fg, bg) => {
        expect(contrast(theme[fg], theme[bg])).toBeGreaterThanOrEqual(4.5);
    });

    it.each(graphicPairs)(
        '%s sobre %s tem contraste para elementos gráficos',
        (fg, bg) => {
            expect(contrast(theme[fg], theme[bg])).toBeGreaterThanOrEqual(3);
        },
    );
});
