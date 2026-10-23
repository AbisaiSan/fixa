import { ptBR } from './pt-BR';

type Leaves<T, P extends string = ''> = {
    [K in keyof T & string]: T[K] extends string
        ? `${P}${K}`
        : Leaves<T[K], `${P}${K}.`>;
}[keyof T & string];

export type StringKey = Leaves<typeof ptBR>;

/**
 * Busca um texto da interface e substitui {parametros}.
 */
export function t(
    key: StringKey,
    params: Record<string, string | number> = {},
): string {
    const value = key
        .split('.')
        .reduce<unknown>(
            (node, part) => (node as Record<string, unknown>)[part],
            ptBR,
        );

    return String(value).replace(/\{(\w+)\}/g, (match, name: string) =>
        name in params ? String(params[name]) : match,
    );
}

/**
 * Nome do idioma em pt-BR a partir do código, ou o próprio código se faltar no arquivo.
 */
export function languageName(code: string): string {
    return code in ptBR.languages
        ? ptBR.languages[code as keyof typeof ptBR.languages]
        : code;
}
