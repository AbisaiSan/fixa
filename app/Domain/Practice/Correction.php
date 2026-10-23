<?php

namespace App\Domain\Practice;

/**
 * Correção de um item (ATV-02): resultado, erro apontado, explicação em
 * português, versão corrigida e alternativa mais natural. Diz também quem
 * corrigiu, o gabarito ou a IA.
 */
final readonly class Correction
{
    public const BY_KEY = 'key';

    public const BY_AI = 'ai';

    public function __construct(
        public Verdict $verdict,
        public string $explanation,
        public string $corrected,
        public string $natural,
        public ?string $error = null,
        public ?ErrorCategory $category = null,
        public ?string $point = null,
        public string $source = self::BY_KEY,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'verdict' => $this->verdict->value,
            'error' => $this->error,
            'category' => $this->category?->value,
            'explanation' => $this->explanation,
            'corrected' => $this->corrected,
            'natural' => $this->natural,
            'point' => $this->point,
            'source' => $this->source,
        ];
    }
}
