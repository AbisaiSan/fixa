<?php

namespace App\Domain\Practice;

/**
 * Itens de escrita do MVP (ATV-01): completar frase, traduzir do português
 * e formar frase num contexto. A escolha entre opções é do ponto fraco.
 */
enum ItemType: string
{
    case Fill = 'fill';
    case Translate = 'translate';
    case Context = 'context';
    case Choice = 'choice';

    /**
     * Tem gabarito fixo: dá para corrigir sem IA quando a resposta bate (ATV-02).
     */
    public function hasAnswerKey(): bool
    {
        return $this !== self::Context;
    }
}
