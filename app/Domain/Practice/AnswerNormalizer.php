<?php

namespace App\Domain\Practice;

use Illuminate\Support\Str;

/**
 * Deixa respostas comparáveis (J4-B2, ATV-02): sem diferença de caixa,
 * espaços, acentos, aspas, pontuação final, tipo de apóstrofo e contrações
 * ("I've" e "I have" são a mesma resposta).
 */
final class AnswerNormalizer
{
    /** @var array<string, string> */
    private const CONTRACTIONS = [
        "can't" => 'cannot',
        "won't" => 'will not',
        "shan't" => 'shall not',
        "let's" => 'let us',
        "n't" => ' not',
        "'re" => ' are',
        "'m" => ' am',
        "'ll" => ' will',
        "'ve" => ' have',
        "'d" => ' would',
    ];

    public static function normalize(string $answer): string
    {
        $text = (string) preg_replace("/[\u{2019}\u{2018}\u{201B}`\u{00B4}]/u", "'", $answer);
        $text = Str::of($text)->ascii()->lower()->squish()->value();
        $text = trim($text, " \"\u{201C}\u{201D}");
        $text = rtrim($text, ' .!?,;:');
        $text = trim($text, ' "');

        $text = strtr($text, self::CONTRACTIONS);

        return Str::squish($text);
    }
}
