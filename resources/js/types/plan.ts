export type BlockKind = 'flashcards' | 'lesson' | 'weak_point';

export type LessonActivity = 'writing' | 'reading' | 'vocabulary';

export type PlanBlock = {
    ulid: string;
    kind: BlockKind;
    activity: LessonActivity | null;
    minutes: number;
    count: number;
    done: boolean;
    skipped: boolean;
    moved_from: string | null;
    href: string | null;
    lesson: { ulid: string; title: string; lesson_date: string } | null;
};

export type TodayPlan = {
    date: string;
    goal: {
        minutes: number;
        done: number;
        planned: number;
        percent: number;
        counted: boolean;
    };
    blocks: PlanBlock[];
    next: string | null;
    streak: { current: number; freezes: number; max_freezes: number };
    xp: { total: number; level: number };
    rush: { due: number; done: boolean };
    resume: 'welcome_back' | 'freeze_used' | null;
};
