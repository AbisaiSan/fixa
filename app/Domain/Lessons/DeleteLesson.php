<?php

namespace App\Domain\Lessons;

use App\Models\Card;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\User;
use App\Support\Audit\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Remove a aula com a revisão, os reports e os arquivos originais (MAT-05, DAD-01).
 * Cartões só desta aula saem junto; os que também vieram de outra aula ficam,
 * apontando para ela (FLA-05).
 */
final class DeleteLesson
{
    public function __construct(private AuditLog $audit) {}

    public function handle(User $user, Lesson $lesson): void
    {
        $paths = LessonFile::forUser($user)->where('lesson_id', $lesson->id)->pluck('path')->all();
        $ulid = $lesson->ulid;

        DB::transaction(function () use ($user, $lesson) {
            Card::forUser($user)
                ->whereHas('lessons', fn ($query) => $query->whereKey($lesson->id))
                ->with('lessons:id')
                ->get()
                ->each(function (Card $card) use ($lesson) {
                    $others = $card->lessons->pluck('id')->reject(fn (int $id) => $id === $lesson->id);

                    if ($others->isEmpty()) {
                        $card->delete();
                    } elseif ($card->source_lesson_id === $lesson->id) {
                        $card->forceFill(['source_lesson_id' => $others->first()])->save();
                    }
                });

            $lesson->delete();
        });

        Storage::disk(LessonFile::DISK)->delete($paths);
        Storage::disk(LessonFile::DISK)->deleteDirectory("{$user->ulid}/{$ulid}");

        $this->audit->record('lesson.deleted', $user, ['lesson' => $ulid]);
    }
}
