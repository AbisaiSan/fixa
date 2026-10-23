import { HttpError, postJson } from '@/lib/http';

/**
 * Fila de respostas aos cartões (J3-F7). Sem conexão, as respostas ficam
 * guardadas no aparelho e vão, na ordem, quando a conexão volta. Cada uma
 * leva o client_review_id, então reenviar não conta duas vezes no servidor.
 * Guarda só o ULID do cartão, a nota e o tempo, nunca texto do aluno.
 */
export type PendingReview = {
    card: string;
    rating: number;
    duration_ms: number;
    client_review_id: string;
};

export type SentReview = {
    card: string;
    interval_days: number;
    preview: number[];
};

const STORAGE_KEY = 'fixa-review-outbox';

// Erros em que reenviar não adianta: o cartão sumiu ou o pedido é inválido.
const GIVE_UP = [403, 404, 422];

let flushing: Promise<void> | null = null;

export function pendingReviews(): PendingReview[] {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        return stored ? (JSON.parse(stored) as PendingReview[]) : [];
    } catch {
        return [];
    }
}

function save(reviews: PendingReview[]): void {
    try {
        if (reviews.length === 0) {
            localStorage.removeItem(STORAGE_KEY);
        } else {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(reviews));
        }
    } catch {
        // Sem armazenamento (aba privada): a fila vive só na memória da sessão.
    }
}

export function enqueueReview(review: PendingReview): void {
    save([...pendingReviews(), review]);
}

/**
 * Envia o que estiver na fila. Para no primeiro erro de rede e tenta de
 * novo depois, mantendo a ordem das respostas.
 */
export function flushReviews(
    onSent: (sent: SentReview) => void = () => {},
): Promise<void> {
    if (flushing) {
        return flushing;
    }

    flushing = send(onSent).finally(() => {
        flushing = null;
    });

    return flushing;
}

async function send(onSent: (sent: SentReview) => void): Promise<void> {
    for (const review of pendingReviews()) {
        try {
            const sent = await postJson<SentReview>(
                `/praticar/flashcards/${review.card}/respostas`,
                {
                    rating: review.rating,
                    duration_ms: review.duration_ms,
                    client_review_id: review.client_review_id,
                },
            );
            onSent(sent);
        } catch (error) {
            if (
                !(error instanceof HttpError) ||
                !GIVE_UP.includes(error.status)
            ) {
                return;
            }
        }

        save(
            pendingReviews().filter(
                (item) => item.client_review_id !== review.client_review_id,
            ),
        );
    }
}
