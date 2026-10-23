<?php

namespace App\Domain\Lessons\Extraction;

use App\Domain\Ai\AiImage;
use App\Domain\Ai\AiTask;
use App\Domain\Ai\Schemas\TextExtractionSchema;
use App\Domain\Ai\StructuredGenerator;
use App\Domain\Lessons\LessonRejected;
use App\Domain\Lessons\LessonSource;
use App\Domain\Lessons\Scanning\VirusScanner;
use App\Domain\Lessons\WordCounter;
use App\Http\Requests\Lessons\StoreLessonRequest;
use App\Models\Lesson;
use App\Models\LessonFile;
use Illuminate\Support\Facades\Storage;

/**
 * Antivírus e extração do texto da aula (J2-B4, J2-B5, ADR 0002).
 *
 * PDF com texto sai pelo poppler, sem IA. Fotos e PDFs escaneados vão para
 * a IA com visão. Material com menos de 50 palavras não segue (MAT-04).
 */
final class TextExtractor
{
    public const MIN_WORDS = 50;

    public function __construct(
        private VirusScanner $scanner,
        private PdfInspector $pdf,
        private StructuredGenerator $generator,
    ) {}

    /**
     * @throws LessonRejected
     */
    public function extract(Lesson $lesson): string
    {
        $files = $lesson->files()->withoutGlobalScopes()->get();

        foreach ($files as $file) {
            $this->scan($file);
        }

        $text = match ($lesson->source) {
            LessonSource::Text => (string) $lesson->extracted_text,
            LessonSource::Pdf => $this->fromPdf($lesson, $files->firstOrFail()),
            LessonSource::Image => $this->fromImages($lesson, $files->values()->all()),
        };

        if (WordCounter::count($text) < self::MIN_WORDS) {
            throw new LessonRejected('too_short');
        }

        return trim($text);
    }

    private function scan(LessonFile $file): void
    {
        $contents = Storage::disk(LessonFile::DISK)->get($file->path);

        if ($contents === null || ! $this->scanner->isClean($contents)) {
            // Arquivo infectado não fica guardado.
            Storage::disk(LessonFile::DISK)->delete($file->path);

            throw new LessonRejected('virus_found');
        }

        $file->forceFill(['scanned_at' => now()])->save();
    }

    private function fromPdf(Lesson $lesson, LessonFile $file): string
    {
        return $this->withLocalCopy($file, function (string $path) use ($lesson) {
            $text = $this->pdf->text($path);

            if ($text === null) {
                throw new LessonRejected('unreadable_file');
            }

            if (WordCounter::count($text) >= self::MIN_WORDS) {
                return $text;
            }

            // Pouco texto selecionável: provavelmente escaneado. A IA lê as páginas.
            $pages = $this->pdf->pagesAsImages($path, StoreLessonRequest::MAX_PAGES);

            return $pages === [] ? $text : $this->readWithAi($lesson, array_map(
                fn (string $page) => new AiImage('image/jpeg', base64_encode($page)),
                $pages,
            ));
        });
    }

    /**
     * @param  array<int, LessonFile>  $files
     */
    private function fromImages(Lesson $lesson, array $files): string
    {
        return $this->readWithAi($lesson, array_values(array_map(
            fn (LessonFile $file) => new AiImage($file->mime_type, base64_encode((string) Storage::disk(LessonFile::DISK)->get($file->path))),
            $files,
        )));
    }

    /**
     * @param  list<AiImage>  $images
     */
    private function readWithAi(Lesson $lesson, array $images): string
    {
        $response = $this->generator->generate(
            AiTask::TextExtraction,
            new TextExtractionSchema,
            'Transcreva as imagens anexadas.',
            ['target_language' => self::languageName($lesson->language)],
            $images,
        );

        if (! $response->data['legible']) {
            throw new LessonRejected('illegible');
        }

        return (string) $response->data['text'];
    }

    /**
     * O poppler precisa de um arquivo local; funciona com disco local ou S3.
     *
     * @template T
     *
     * @param  callable(string): T  $callback
     * @return T
     */
    private function withLocalCopy(LessonFile $file, callable $callback): mixed
    {
        $path = tempnam(sys_get_temp_dir(), 'lesson');
        file_put_contents($path, (string) Storage::disk(LessonFile::DISK)->get($file->path));

        try {
            return $callback($path);
        } finally {
            @unlink($path);
        }
    }

    public static function languageName(string $code): string
    {
        return ['en' => 'inglês', 'es' => 'espanhol', 'fr' => 'francês'][$code] ?? $code;
    }
}
