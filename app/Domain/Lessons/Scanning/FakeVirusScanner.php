<?php

namespace App\Domain\Lessons\Scanning;

/**
 * Antivírus de teste: acusa a assinatura de teste EICAR, como o real faria.
 */
final class FakeVirusScanner implements VirusScanner
{
    // Montada em partes para o próprio arquivo de código não disparar antivírus.
    public const EICAR = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$'.'EICAR-STANDARD-'.'ANTIVIRUS-TEST-FILE!$H+H*';

    public bool $unavailable = false;

    public function isClean(string $contents): bool
    {
        if ($this->unavailable) {
            throw new ScannerUnavailable('Fake scanner offline.');
        }

        return ! str_contains($contents, self::EICAR);
    }
}
