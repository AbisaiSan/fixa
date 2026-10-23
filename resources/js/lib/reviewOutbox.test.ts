import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { enqueueReview, flushReviews, pendingReviews } from './reviewOutbox';

const review = (id: string) => ({
    card: `card-${id}`,
    rating: 3,
    duration_ms: 1000,
    client_review_id: id,
});

const ok = () =>
    new Response(
        JSON.stringify({ card: 'x', interval_days: 2, preview: [0, 1, 2, 4] }),
    );

describe('review outbox', () => {
    beforeEach(() => localStorage.clear());
    afterEach(() => vi.unstubAllGlobals());

    it('keeps answers while offline and sends them in order when back', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => Promise.reject(new TypeError('offline'))),
        );
        enqueueReview(review('1'));
        enqueueReview(review('2'));

        await flushReviews();

        expect(pendingReviews().map((item) => item.client_review_id)).toEqual([
            '1',
            '2',
        ]);

        const fetch = vi.fn(async () => ok());
        vi.stubGlobal('fetch', fetch);

        await flushReviews();

        expect(pendingReviews()).toEqual([]);
        expect(
            fetch.mock.calls.map((call) => (call as unknown as [string])[0]),
        ).toEqual([
            '/praticar/flashcards/card-1/respostas',
            '/praticar/flashcards/card-2/respostas',
        ]);
    });

    it('drops an answer the server will never accept and keeps going', async () => {
        const fetch = vi
            .fn()
            .mockResolvedValueOnce(new Response('', { status: 404 }))
            .mockResolvedValueOnce(ok());
        vi.stubGlobal('fetch', fetch);
        enqueueReview(review('1'));
        enqueueReview(review('2'));

        await flushReviews();

        expect(pendingReviews()).toEqual([]);
        expect(fetch).toHaveBeenCalledTimes(2);
    });

    it('stops at a server error and retries later, keeping the order', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => new Response('', { status: 500 })),
        );
        enqueueReview(review('1'));

        await flushReviews();

        expect(pendingReviews()).toHaveLength(1);
    });

    it('never stores student text', () => {
        enqueueReview(review('1'));

        expect(Object.keys(pendingReviews()[0]).sort()).toEqual([
            'card',
            'client_review_id',
            'duration_ms',
            'rating',
        ]);
    });
});

describe('review outbox after an empty flush', () => {
    beforeEach(() => localStorage.clear());
    afterEach(() => vi.unstubAllGlobals());

    it('still sends what comes next', async () => {
        const fetch = vi.fn(async () => ok());
        vi.stubGlobal('fetch', fetch);

        await flushReviews();
        enqueueReview(review('1'));
        await flushReviews();

        expect(fetch).toHaveBeenCalledOnce();
    });
});
