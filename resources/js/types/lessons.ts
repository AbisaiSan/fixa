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
