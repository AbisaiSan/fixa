<?php

namespace App\Domain\Lessons\Extraction;

use Illuminate\Support\Facades\Process;

/**
 * Lê informações e texto de PDFs com o poppler, em processo separado e com
 * limite de tempo (UPL-01, UPL-03, ADR 0002).
 */
class PdfInspector
{
    public const TIMEOUT_SECONDS = 20;

    /**
     * Número de páginas, ou null se o arquivo não for um PDF legível.
     */
    public function pages(string $path): ?int
    {
        $result = Process::timeout(self::TIMEOUT_SECONDS)->run(['pdfinfo', $path]);

        if ($result->failed() || ! preg_match('/^Pages:\s+(\d+)/m', $result->output(), $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Texto do PDF, mantendo a disposição das linhas. Null se falhar.
     */
    public function text(string $path): ?string
    {
        $result = Process::timeout(self::TIMEOUT_SECONDS)->run(['pdftotext', '-layout', '-enc', 'UTF-8', $path, '-']);

        return $result->successful() ? $result->output() : null;
    }

    public static function available(): bool
    {
        return Process::run(['which', 'pdfinfo'])->successful();
    }
}
