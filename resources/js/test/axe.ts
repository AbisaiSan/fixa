import axe from 'axe-core';
import { expect } from 'vite-plus/test';

/**
 * Roda o axe no elemento montado e falha listando as violações (J0-F8).
 * O jsdom não calcula cores, então o contraste é verificado em tokens.test.ts.
 */
export async function expectAccessible(element: Element): Promise<void> {
    const results = await axe.run(element, {
        rules: { 'color-contrast': { enabled: false } },
    });

    const violations = results.violations.map(
        (violation) =>
            `${violation.id}: ${violation.help} (${violation.nodes.map((node) => node.html).join(', ')})`,
    );

    expect(violations).toEqual([]);
}
