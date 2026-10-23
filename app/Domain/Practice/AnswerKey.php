<?php

namespace App\Domain\Practice;

/**
 * Correção com gabarito fixo, sem IA (J4-B2, ATV-02). A resposta, depois de
 * normalizada, é comparada com as respostas aceitas e, no "completar", também
 * com a frase inteira já preenchida. Um erro de digitação pequeno vale como
 * parcial (ATV-03). O que não bater fica para a correção por IA (J4-B3).
 */
final class AnswerKey
{
    /**
     * @param  array<string, mixed>  $content
     */
    public static function check(ItemType $type, array $content, string $answer): AnswerCheck
    {
        $given = AnswerNormalizer::normalize($answer);

        if ($given === '' || ! $type->hasAnswerKey()) {
            return new AnswerCheck(Verdict::NoMatch);
        }

        $candidates = self::candidates($type, $content);

        foreach ($candidates as $normalized => $original) {
            if ($given === (string) $normalized) {
                return new AnswerCheck(Verdict::Total, $original);
            }
        }

        // Na escolha entre opções não existe erro de digitação.
        if ($type === ItemType::Choice) {
            return new AnswerCheck(Verdict::NoMatch);
        }

        foreach ($candidates as $normalized => $original) {
            $distance = levenshtein($given, (string) $normalized);

            if ($distance <= self::typoAllowance((string) $normalized)) {
                return new AnswerCheck(Verdict::Partial, $original);
            }
        }

        return new AnswerCheck(Verdict::NoMatch);
    }

    /**
     * Respostas aceitas normalizadas, apontando para a forma original.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private static function candidates(ItemType $type, array $content): array
    {
        /** @var list<string> $accepted */
        $accepted = $content['accepted'] ?? [];
        $candidates = [];

        foreach ($accepted as $answer) {
            $candidates[AnswerNormalizer::normalize($answer)] ??= $answer;
        }

        // No "completar", a frase inteira com a lacuna preenchida também vale.
        if ($type === ItemType::Fill && isset($content['sentence'])) {
            $sentence = (string) preg_replace('/\s*\([^)]*\)/', '', (string) $content['sentence']);

            foreach ($accepted as $answer) {
                $full = str_replace('___', $answer, $sentence);
                $candidates[AnswerNormalizer::normalize($full)] ??= $full;
            }
        }

        unset($candidates['']);

        return $candidates;
    }

    /**
     * Erro de digitação tolerado: uma letra em respostas curtas, duas nas longas.
     */
    private static function typoAllowance(string $answer): int
    {
        return strlen($answer) <= 12 ? 1 : 2;
    }
}
