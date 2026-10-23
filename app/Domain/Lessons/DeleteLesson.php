<?php

namespace App\Domain\Lessons;

use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\User;
use App\Support\Audit\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Remove a aula com a revisão, os reports e os arquivos originais (MAT-05, DAD-01).
 */
final class DeleteLesson
{
    public function __construct(private AuditLog $audit) {}

    public function handle(User $user, Lesson $lesson): void
    {
        $paths = LessonFile::forUser($user)->where('lesson_id', $lesson->id)->pluck('path')->all();
        $ulid = $lesson->ulid;

        DB::transaction(fn () => $lesson->delete());

        Storage::disk(LessonFile::DISK)->delete($paths);
        Storage::disk(LessonFile::DISK)->deleteDirectory("{$user->ulid}/{$ulid}");

        $this->audit->record('lesson.deleted', $user, ['lesson' => $ulid]);
    }
}
