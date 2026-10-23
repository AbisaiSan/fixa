<?php

namespace App\Domain\Vocabulary;

use App\Domain\Cards\Scheduling\Rating;

/**
 * Estado da palavra (VOC-01, VOC-02), calculado pelo desempenho no cartão:
 * nova até a primeira resposta, dominada com intervalo de 21 dias ou mais e
 * nenhum "Errei" nas 3 últimas respostas, aprendendo no resto.
 */
enum WordState: string
{
    case New = 'new';
    case Learning = 'learning';
    case Mastered = 'mastered';

    public const MASTERED_INTERVAL = 21;

    public const CHECKED_ANSWERS = 3;

    /**
     * @param  list<Rating>  $lastRatings  da mais recente para a mais antiga
     */
    public static function forCard(int $intervalDays, array $lastRatings): self
    {
        if ($lastRatings === []) {
            return self::New;
        }

        $checked = array_slice($lastRatings, 0, self::CHECKED_ANSWERS);

        $mastered = $intervalDays >= self::MASTERED_INTERVAL
            && count($checked) === self::CHECKED_ANSWERS
            && ! in_array(Rating::Again, $checked, true);

        return $mastered ? self::Mastered : self::Learning;
    }
}
