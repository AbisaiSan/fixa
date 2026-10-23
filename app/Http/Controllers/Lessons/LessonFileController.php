<?php

namespace App\Http\Controllers\Lessons;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Entrega o arquivo original só por link assinado e temporário, ao dono
 * da aula (UPL-02).
 */
class LessonFileController extends Controller
{
    public const LINK_MINUTES = 5;

    public function show(Lesson $lesson, LessonFile $file): StreamedResponse
    {
        Gate::authorize('view', $lesson);
        abort_unless($file->lesson_id === $lesson->id, 404);

        $response = Storage::disk(LessonFile::DISK)->response($file->path, "aula-{$file->position}", [
            'Content-Type' => $file->mime_type,
            'Cache-Control' => 'private, no-store',
        ]);

        // Mesmo um PDF malicioso abre sem poder rodar script nem navegar.
        $response->headers->set('Content-Security-Policy', 'sandbox; default-src \'none\'; img-src \'self\' data:; style-src \'unsafe-inline\'');

        return $response;
    }
}
