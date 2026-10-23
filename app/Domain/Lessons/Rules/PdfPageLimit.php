<?php

namespace App\Domain\Lessons\Rules;

use App\Domain\Lessons\Extraction\PdfInspector;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * PDF legível e com até N páginas (MAT-02).
 */
final readonly class PdfPageLimit implements ValidationRule
{
    public function __construct(private int $maxPages) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $pages = app(PdfInspector::class)->pages($value->getRealPath());

        if ($pages === null) {
            $fail('Não conseguimos abrir este PDF. Confira se ele não está protegido por senha.');
        } elseif ($pages > $this->maxPages) {
            $fail("O PDF tem {$pages} páginas. O limite é {$this->maxPages} por envio.");
        }
    }
}
