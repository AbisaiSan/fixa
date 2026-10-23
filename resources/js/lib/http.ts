/**
 * Pedido JSON ao próprio app, com o token CSRF do cookie XSRF-TOKEN que o
 * Laravel envia. Usado nas sessões de prática, que não recarregam a página.
 */
export async function postJson<T>(url: string, body: unknown): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        throw new HttpError(response.status);
    }

    return (await response.json()) as T;
}

export class HttpError extends Error {
    constructor(public readonly status: number) {
        super(`HTTP ${status}`);
    }
}

function xsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find((part) => part.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : '';
}
