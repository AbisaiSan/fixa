<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Lessons\Extraction\PdfInspector;
use App\Domain\Lessons\Extraction\TextExtractor;
use App\Domain\Lessons\LessonRejected;
use App\Domain\Lessons\LessonSource;
use App\Domain\Lessons\Scanning\ClamAvScanner;
use App\Domain\Lessons\Scanning\FakeVirusScanner;
use App\Domain\Lessons\Scanning\ScannerUnavailable;
use App\Domain\Lessons\WordCounter;
use App\Models\Lesson;
use App\Models\LessonFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(LessonFile::DISK);
    $this->ai = app(FakeAiProvider::class);
    $this->pdfText = str_repeat('I have worked here since 2022. ', 12);
    app()->instance(PdfInspector::class, new class($this) extends PdfInspector
    {
        public function __construct(private object $test) {}

        public function text(string $path): ?string
        {
            return $this->test->pdfText;
        }

        public function pagesAsImages(string $path, int $maxPages): array
        {
            return ['pagina-1', 'pagina-2'];
        }
    });
});

function lessonWithFile(LessonSource $source, string $contents = '%PDF conteúdo', string $mime = 'application/pdf'): Lesson
{
    $lesson = Lesson::factory()->create(['source' => $source]);
    Storage::disk(LessonFile::DISK)->put('x/a', $contents);
    (new LessonFile)->forceFill([
        'user_id' => $lesson->user_id, 'lesson_id' => $lesson->id, 'path' => 'x/a',
        'mime_type' => $mime, 'size_bytes' => strlen($contents), 'sha256' => str_repeat('0', 64),
    ])->save();

    return $lesson;
}

test('words are counted in any language', function () {
    expect(WordCounter::count("I've worked here, não é? 2022"))->toBe(6)
        ->and(WordCounter::count(null))->toBe(0);
});

test('pasted text with enough words goes on unchanged', function () {
    $lesson = Lesson::factory()->create();
    $lesson->forceFill(['extracted_text' => $this->pdfText])->save();

    expect(app(TextExtractor::class)->extract($lesson))->toBe(trim($this->pdfText));
    $this->ai->assertSentCount(0);
});

test('material under 50 words is refused before any AI call', function () {
    $lesson = Lesson::factory()->create();
    $lesson->forceFill(['extracted_text' => 'Present perfect: have + past participle.'])->save();

    expect(fn () => app(TextExtractor::class)->extract($lesson))
        ->toThrow(fn (LessonRejected $e) => expect($e->reason)->toBe('too_short'));
    $this->ai->assertSentCount(0);
});

test('a pdf with text is read by poppler, without AI', function () {
    $lesson = lessonWithFile(LessonSource::Pdf);

    expect(app(TextExtractor::class)->extract($lesson))->toContain('I have worked here');
    $this->ai->assertSentCount(0);
    expect(LessonFile::withoutGlobalScopes()->sole()->scanned_at)->not->toBeNull();
});

test('a scanned pdf is read by the AI page by page', function () {
    $this->pdfText = 'quase nada';
    $this->ai->respondWith(AiTask::TextExtraction, ['text' => $this->pdfText.str_repeat(' palavra lida', 60), 'legible' => true]);

    app(TextExtractor::class)->extract(lessonWithFile(LessonSource::Pdf));

    expect($this->ai->sent(AiTask::TextExtraction)[0]->images)->toHaveCount(2);
});

test('photos are read by the AI and illegible ones are refused', function () {
    $this->ai->respondWith(AiTask::TextExtraction, ['text' => '', 'legible' => false]);

    expect(fn () => app(TextExtractor::class)->extract(lessonWithFile(LessonSource::Image, 'jpeg', 'image/jpeg')))
        ->toThrow(fn (LessonRejected $e) => expect($e->reason)->toBe('illegible'));
});

test('an infected file is refused and deleted', function () {
    $lesson = lessonWithFile(LessonSource::Pdf, '%PDF '.FakeVirusScanner::EICAR);

    expect(fn () => app(TextExtractor::class)->extract($lesson))
        ->toThrow(fn (LessonRejected $e) => expect($e->reason)->toBe('virus_found'));
    Storage::disk(LessonFile::DISK)->assertMissing('x/a');
});

test('without the antivirus the file does not go on', function () {
    app(FakeVirusScanner::class)->unavailable = true;

    expect(fn () => app(TextExtractor::class)->extract(lessonWithFile(LessonSource::Pdf)))->toThrow(ScannerUnavailable::class);
});

test('the real clamav client detects the test signature', function () {
    $scanner = new ClamAvScanner((string) config('fixa.antivirus.host'), (int) config('fixa.antivirus.port'));

    expect($scanner->isClean('arquivo limpo'))->toBeTrue()
        ->and($scanner->isClean(FakeVirusScanner::EICAR))->toBeFalse();
})->skip(fn () => @fsockopen((string) config('fixa.antivirus.host'), (int) config('fixa.antivirus.port'), $c, $m, 1) === false, 'clamd fora de alcance; rode dentro do Sail.');
