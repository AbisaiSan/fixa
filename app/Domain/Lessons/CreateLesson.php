<?php

namespace App\Domain\Lessons;

use App\Domain\Lessons\Extraction\PdfInspector;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Registra a aula enviada (MAT-01, MAT-07) e guarda os arquivos com nome
 * aleatório no disco privado (UPL-02). O processamento vem depois, na fila.
 */
final class CreateLesson
{
    public function __construct(private PdfInspector $pdf) {}

    /**
     * @param  list<UploadedFile>  $files
     */
    public function handle(User $user, LessonSource $source, string $title, string $lessonDate, ?string $text, array $files): Lesson
    {
        $enrollment = $user->activeEnrollment;
        $stored = [];

        try {
            return DB::transaction(function () use ($user, $enrollment, $source, $title, $lessonDate, $text, $files, &$stored) {
                $lesson = (new Lesson(['title' => $title, 'lesson_date' => $lessonDate]))->forceFill([
                    'user_id' => $user->id,
                    'enrollment_id' => $enrollment?->id,
                    'language' => $enrollment->target_language ?? config('fixa.target_language'),
                    'source' => $source,
                    'status' => LessonStatus::Queued,
                    'extracted_text' => $source === LessonSource::Text ? $text : null,
                    'rights_declared_at' => now(),
                ]);
                $lesson->save();

                foreach ($files as $index => $file) {
                    $path = $this->store($user, $lesson, $file);
                    $stored[] = $path;

                    (new LessonFile)->forceFill([
                        'user_id' => $user->id,
                        'lesson_id' => $lesson->id,
                        'path' => $path,
                        'mime_type' => (string) $file->getMimeType(),
                        'size_bytes' => (int) $file->getSize(),
                        'pages' => $this->pdf->pages($file->getRealPath()),
                        'position' => $index + 1,
                        'sha256' => (string) hash_file('sha256', $file->getRealPath()),
                    ])->save();
                }

                return $lesson;
            });
        } catch (\Throwable $exception) {
            Storage::disk(LessonFile::DISK)->delete($stored);

            throw $exception;
        }
    }

    private function store(User $user, Lesson $lesson, UploadedFile $file): string
    {
        $path = Storage::disk(LessonFile::DISK)->putFileAs(
            "{$user->ulid}/{$lesson->ulid}",
            $file,
            Str::random(40).'.pdf',
        );

        if ($path === false) {
            throw new RuntimeException('The lesson file could not be stored.');
        }

        return $path;
    }
}
