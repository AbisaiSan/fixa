<?php

namespace App\Domain\Progress;

/**
 * De onde vem o XP (GAM-03). Os valores seguem o protótipo.
 */
enum XpSource: string
{
    /** Cada resposta a um cartão. */
    case CardReview = 'card_review';
    /** Cada item respondido, com o XP proporcional ao resultado (ATV-03). */
    case Attempt = 'attempt';
    /** Bloco de flashcards do dia concluído. */
    case Flashcards = 'flashcards';
    /** Modo corrida concluído. */
    case Rush = 'rush';
    /** Sessão de escrita, leitura ou ponto fraco concluída. */
    case Session = 'session';

    public const CARD_REVIEW_XP = 2;

    public const FLASHCARDS_XP = 10;

    public const SESSION_XP = 10;

    public const READING_XP = 20;
}
