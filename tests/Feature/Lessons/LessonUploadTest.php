<?php

use App\Domain\Lessons\Extraction\PdfInspector;
use App\Domain\Lessons\LessonSource;
use App\Domain\Lessons\LessonStatus;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Storage::fake(LessonFile::DISK);
    $this->user = User::factory()->create();
    $this->pages = 3;
    app()->instance(PdfInspector::class, new class($this) extends PdfInspector
    {
        public function __construct(private object $test) {}

        public function pages(string $path): ?int
        {
            return str_starts_with((string) file_get_contents($path), '%PDF') ? $this->test->pages : null;
        }
    });
});

function minimalPdf(): string
{
    return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";
}

/**
 * Arquivo de verdade: o tipo é examinado pelo conteúdo, como num envio real.
 * O UploadedFile::fake() informa o tipo pela extensão e esconderia o disfarce.
 */
function realUpload(string $name, string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'up');
    file_put_contents($path, $content);

    return new UploadedFile($path, $name, null, null, true);
}

function upload(array $data)
{
    return test()->actingAs(test()->user)->post(route('lessons.store'), [
        'title' => 'Present Perfect no trabalho',
        'lesson_date' => '2026-10-21',
        'rights' => '1',
        ...$data,
    ]);
}

test('pasted text becomes a queued lesson with the text kept encrypted', function () {
    upload(['source' => 'text', 'text' => 'I have worked here since 2022.'])->assertSessionHasNoErrors();

    $lesson = Lesson::forUser($this->user)->sole();

    expect($lesson->source)->toBe(LessonSource::Text)
        ->and($lesson->status)->toBe(LessonStatus::Queued)
        ->and($lesson->extracted_text)->toBe('I have worked here since 2022.')
        ->and($lesson->rights_declared_at)->not->toBeNull()
        ->and($lesson->enrollment_id)->toBe($this->user->activeEnrollment->id);
});

test('the student must declare the right to use the material', function () {
    upload(['source' => 'text', 'text' => 'texto', 'rights' => null])
        ->assertSessionHasErrors(['rights' => 'Para enviar, declare que tem direito de usar este material.']);

    expect(Lesson::forUser($this->user)->count())->toBe(0);
});

test('a pdf is stored with a random name in a private folder', function () {
    upload(['source' => 'pdf', 'pdf' => realUpload('Minha Aula.pdf', minimalPdf())])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $file = LessonFile::forUser($this->user)->sole();

    expect($file->mime_type)->toBe('application/pdf')
        ->and($file->pages)->toBe(3)
        ->and($file->path)->toStartWith($this->user->ulid.'/')
        ->and($file->path)->not->toContain('Minha')
        ->and($file->path)->toMatch('/\/[A-Za-z0-9]{40}\.pdf$/')
        ->and($file->sha256)->toBe(hash('sha256', minimalPdf()));
    Storage::disk(LessonFile::DISK)->assertExists($file->path);
});

test('a file renamed to pdf is refused by its real type', function () {
    upload(['source' => 'pdf', 'pdf' => realUpload('aula.pdf', "MZ\x90\x00 isto é um executável")])
        ->assertSessionHasErrors(['pdf' => 'Este arquivo não é um PDF de verdade.']);

    expect(Lesson::forUser($this->user)->count())->toBe(0);
    expect(Storage::disk(LessonFile::DISK)->allFiles())->toBe([]);
});

test('a pdf over 20 pages is refused', function () {
    $this->pages = 21;

    upload(['source' => 'pdf', 'pdf' => UploadedFile::fake()->createWithContent('aula.pdf', minimalPdf())])
        ->assertSessionHasErrors(['pdf' => 'O PDF tem 21 páginas. O limite é 20 por envio.']);
});

test('a file over 10 MB is refused', function () {
    upload(['source' => 'pdf', 'pdf' => UploadedFile::fake()->create('aula.pdf', 10 * 1024 + 1, 'application/pdf')])
        ->assertSessionHasErrors('pdf');
});

test('photos are accepted by real image type and kept in order', function () {
    upload(['source' => 'image', 'images' => [
        UploadedFile::fake()->image('pagina1.jpg'),
        UploadedFile::fake()->image('pagina2.png'),
    ]])->assertSessionHasNoErrors();

    $files = LessonFile::forUser($this->user)->orderBy('position')->get();

    expect($files->pluck('mime_type')->all())->toBe(['image/jpeg', 'image/png'])
        ->and($files->pluck('position')->all())->toBe([1, 2]);
});

test('a non image renamed to jpg is refused', function () {
    upload(['source' => 'image', 'images' => [realUpload('foto.jpg', '<?php echo 1;')]])
        ->assertSessionHasErrors(['images.0' => 'Envie fotos em JPG, PNG, WEBP ou HEIC.']);
});

test('uploads are rate limited per student', function () {
    foreach (range(1, 5) as $attempt) {
        upload(['source' => 'text', 'text' => 'texto da aula']);
    }

    upload(['source' => 'text', 'text' => 'texto da aula'])->assertTooManyRequests();
});

test('the original file is served only through a valid signed link', function () {
    upload(['source' => 'pdf', 'pdf' => UploadedFile::fake()->createWithContent('aula.pdf', minimalPdf())]);
    $file = LessonFile::forUser($this->user)->sole();
    $lesson = Lesson::forUser($this->user)->sole();
    $params = [$lesson->ulid, $file->ulid];

    $this->actingAs($this->user)->get(route('lessons.files.show', $params))->assertForbidden();

    $response = $this->actingAs($this->user)->get(URL::temporarySignedRoute('lessons.files.show', now()->addMinutes(5), $params));
    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Security-Policy'))->toContain('sandbox')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');

    $this->travel(6)->minutes();
    $this->actingAs($this->user)->get(URL::temporarySignedRoute('lessons.files.show', now()->subMinute(), $params))->assertForbidden();
});

test('the real pdf inspector counts pages with poppler', function () {
    $inspector = new PdfInspector;
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, minimalPdf());

    expect($inspector->pages($path))->toBe(1);

    file_put_contents($path, 'não é pdf');
    expect($inspector->pages($path))->toBeNull();

    unlink($path);
})->skip(fn () => ! PdfInspector::available(), 'poppler não instalado nesta máquina; roda no CI e no container.');
