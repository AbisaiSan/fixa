<?php

namespace App\Domain\Lessons\Scanning;

/**
 * Verificação antivírus dos arquivos enviados (UPL-03).
 */
interface VirusScanner
{
    /**
     * @throws ScannerUnavailable quando o antivírus não responde; o arquivo não deve seguir.
     */
    public function isClean(string $contents): bool;
}
