<?php

namespace App\Domain\Lessons\Extraction;

use App\Domain\Lessons\LessonRejected;
use App\Domain\Lessons\LessonSource;
use App\Domain\Lessons\Scanning\VirusScanner;
use App\Domain\Lessons\WordCounter;
use App\Models\Lesson;
use App\Models\LessonFile;
use Illuminate\Support\Facades\Storage;

/**
 * Antivírus e extração do texto da aula (J2-B4, J2-B5, ADR 0002).
 *
 * O MVP aceita texto colado e PDF com texto, lido pelo poppler sem IA.
 * PDF sem texto selecionável (escaneado ou foto) é recusado, e material com
 * menos de 50 palavras não segue (MAT-04).
 */
final class TextExtractor
{
    public const MIN_WORDS = 50;

    /**
     * Abaixo disso, o PDF praticamente não tem texto: é imagem.
     */
    public const SCANNED_PDF_MAX_WORDS = 10;

    public function __construct(
        private VirusScanner $scanner,
        private PdfInspector $pdf,
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
            LessonSource::Pdf => $this->fromPdf($files->firstOrFail()),
        };

        $words = WordCounter::count($text);

        if ($lesson->source === LessonSource::Pdf && $words <= self::SCANNED_PDF_MAX_WORDS) {
            throw new LessonRejected('pdf_without_text');
        }

        if ($words < self::MIN_WORDS) {
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

    /**
     * O poppler precisa de um arquivo local; funciona com disco local ou S3.
     */
    private function fromPdf(LessonFile $file): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lesson');
        file_put_contents($path, (string) Storage::disk(LessonFile::DISK)->get($file->path));

        try {
            return $this->pdf->text($path) ?? throw new LessonRejected('unreadable_file');
        } finally {
            @unlink($path);
        }
    }
}
