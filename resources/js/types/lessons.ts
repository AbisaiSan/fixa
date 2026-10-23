export type LessonSource = 'text' | 'pdf';

export type LessonStatus =
    | 'queued'
    | 'extracting'
    | 'generating'
    | 'validating'
    | 'ready'
    | 'failed';

export type LessonListItem = {
    ulid: string;
    title: string;
    lesson_date: string;
    source: LessonSource;
    pages: number | null;
    status: LessonStatus;
    concepts: number | null;
};

export type LessonDetail = {
    ulid: string;
    title: string;
    lesson_date: string;
    source: LessonSource;
    pages: number | null;
    language: string;
    level: string | null;
    status: LessonStatus;
    failure_reason: string | null;
    word_count: number | null;
    can_regenerate: boolean;
    can_retry: boolean;
    regenerations_left: number;
};

export type ReviewConcept = {
    key: string;
    name: string;
    explanation: string;
    examples: { target: string; translation: string }[];
};

export type LessonReview = {
    ulid: string;
    summary: string;
    concepts: ReviewConcept[];
    common_mistakes: { wrong: string; right: string; why: string }[];
    can_do: string[];
    can_do_done: number[];
    reported: boolean;
    open_reports: { ulid: string; item: string }[];
};

export type SessionCard = {
    ulid: string;
    kind: 'word' | 'phrase';
    front: string;
    back: string;
    example: string | null;
    example_translation: string | null;
    part_of_speech: string | null;
    lesson_date: string | null;
    is_new: boolean;
    preview: number[];
};

export type WordState = 'new' | 'learning' | 'mastered';

export type WordListItem = {
    ulid: string;
    term: string;
    part_of_speech: string | null;
    translation: string;
    state: WordState;
};

export type WordDetail = WordListItem & {
    definition: string | null;
    examples: { target: string; translation: string }[];
    collocations: string[];
    origin: 'lesson' | 'mine';
    lesson: { ulid: string; lesson_date: string } | null;
    generated: boolean;
    report: string | null;
};
