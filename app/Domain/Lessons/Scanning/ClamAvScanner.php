<?php

namespace App\Domain\Lessons\Scanning;

/**
 * Cliente do clamd pelo protocolo INSTREAM, na rede interna do Sail
 * (ADR 0002). Na dúvida, o arquivo é tratado como não verificado.
 */
final class ClamAvScanner implements VirusScanner
{
    private const CHUNK_BYTES = 8192;

    public function __construct(
        private string $host,
        private int $port,
        private int $timeoutSeconds = 30,
    ) {}

    public function isClean(string $contents): bool
    {
        $socket = @fsockopen($this->host, $this->port, $errorCode, $errorMessage, 5);

        if ($socket === false) {
            throw new ScannerUnavailable('ClamAV is not reachable.');
        }

        stream_set_timeout($socket, $this->timeoutSeconds);

        try {
            fwrite($socket, "zINSTREAM\0");

            foreach (str_split($contents, self::CHUNK_BYTES) as $chunk) {
                fwrite($socket, pack('N', strlen($chunk)).$chunk);
            }

            fwrite($socket, pack('N', 0));
            $reply = trim((string) stream_get_contents($socket), "\0\n ");
        } finally {
            fclose($socket);
        }

        if (str_ends_with($reply, 'OK')) {
            return true;
        }

        if (str_ends_with($reply, 'FOUND')) {
            return false;
        }

        throw new ScannerUnavailable('ClamAV gave an unexpected answer.');
    }
}
