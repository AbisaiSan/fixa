<?php

namespace App\Domain\Practice;

use App\Domain\Cards\CardIdentity;
use App\Domain\Vocabulary\AddOwnWord;
use App\Models\PracticeSession;
use App\Models\User;
use App\Models\Word;
use InvalidArgumentException;

/**
 * Salva no vocabulário, com um toque, uma palavra do texto de leitura
 * (J4-B7, ATV-04, VOC-03). Os dados vêm do glossário do texto, sem IA. A
 * palavra entra como "adicionada por mim", e tocar de novo não duplica.
 */
final class SaveReadingWord
{
    public function __construct(private AddOwnWord $addWord) {}

    public function handle(User $user, PracticeSession $session, string $form): Word
    {
        /** @var list<array{form: string, term: string, part_of_speech: string, translation: string}> $glossary */
        $glossary = $session->content['glossary'] ?? [];
        $entry = null;

        foreach ($glossary as $item) {
            if (mb_strtolower($item['form']) === mb_strtolower(trim($form))) {
                $entry = $item;
                break;
            }
        }

        if ($entry === null) {
            throw new InvalidArgumentException('The word is not in the reading glossary.');
        }

        $existing = Word::forUser($user)->where('dedupe_key', CardIdentity::key($entry['term'], $entry['translation']))->first();

        return $existing ?? $this->addWord->handle($user, [
            'term' => $entry['term'],
            'translation' => $entry['translation'],
            'part_of_speech' => $entry['part_of_speech'],
        ], fillWithAi: false);
    }
}
