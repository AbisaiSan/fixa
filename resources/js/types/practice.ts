export type PracticeCorrection = {
    verdict: 'total' | 'partial' | 'wrong';
    error: string | null;
    category: string | null;
    explanation: string;
    corrected: string;
    natural: string;
    point: string | null;
    source: 'key' | 'ai';
};

export type PracticeAttempt = {
    answer?: string;
    verdict: 'total' | 'partial' | 'wrong';
    xp: number;
    correction: PracticeCorrection;
};

export type PracticeItem = {
    ulid: string;
    position: number;
    type: 'fill' | 'translate' | 'context' | 'choice';
    context: 'daily' | 'work' | 'travel' | null;
    instruction: string | null;
    sentence: string;
    options: string[] | null;
    reported: boolean;
    report?: string | null;
    attempt: PracticeAttempt | null;
};
