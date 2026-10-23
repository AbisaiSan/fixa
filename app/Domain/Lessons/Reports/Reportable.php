<?php

namespace App\Domain\Lessons\Reports;

use App\Models\ItemReport;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Conteúdo gerado com "reportar erro" (MAT-05): a revisão da aula, por
 * partes, e cada cartão inteiro.
 */
interface Reportable
{
    /**
     * Partes que o aluno pode reportar.
     *
     * @return list<string>
     */
    public function itemKeys(): array;

    /**
     * Aula de onde o conteúdo veio (MAT-03).
     */
    public function sourceLessonId(): ?int;

    /**
     * @return MorphMany<ItemReport, *>
     */
    public function reports(): MorphMany;

    /**
     * Atualiza o campo reported conforme os reports abertos.
     */
    public function refreshReported(): void;
}
