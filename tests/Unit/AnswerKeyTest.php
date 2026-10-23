<?php

use App\Domain\Practice\AnswerKey;
use App\Domain\Practice\AnswerNormalizer;
use App\Domain\Practice\ItemType;
use App\Domain\Practice\Verdict;

$translate = [
    'sentence' => 'Já enviei meu currículo.',
    'accepted' => [
        "I've already sent my résumé.",
        'I have already sent my CV.',
        'I already sent my resume.',
        "I've sent my résumé already.",
    ],
    'corrected' => "I've already sent my résumé.",
];

$fill = [
    'sentence' => 'I ___ (work) at this company since 2021.',
    'accepted' => ['have worked', "'ve been working"],
    'corrected' => 'I have worked at this company since 2021.',
];

test('twenty correct variations of a translation are accepted', function (string $answer) use ($translate) {
    expect(AnswerKey::check(ItemType::Translate, $translate, $answer)->verdict)->toBe(Verdict::Total);
})->with([
    "I've already sent my résumé.",
    'I have already sent my résumé.',
    "i've already sent my résumé",
    'I’ve already sent my résumé.',
    'I`ve already sent my résumé.',
    "I've already sent my resume.",
    'I have already sent my resume',
    '  I HAVE ALREADY SENT MY RÉSUMÉ  ',
    'I have  already   sent my resume!',
    'I have already sent my CV.',
    "I've already sent my CV",
    'i have already sent my cv',
    'I already sent my resume.',
    'I already sent my résumé',
    "I've sent my résumé already.",
    'I have sent my resume already',
    "I've sent my resume already!",
    'I have already sent my résumé...',
    '"I have already sent my résumé."',
    "I'VE ALREADY SENT MY CV.",
]);

test('a fill item accepts the missing part or the whole sentence', function (string $answer) use ($fill) {
    expect(AnswerKey::check(ItemType::Fill, $fill, $answer)->verdict)->toBe(Verdict::Total);
})->with([
    'have worked',
    "'ve worked",
    '’ve worked',
    'Have Worked',
    'have been working',
    "'ve been working",
    'I have worked at this company since 2021.',
    "I've worked at this company since 2021",
]);

test('a small typo is partial and the matched answer is shown', function () use ($fill, $translate) {
    $fillTypo = AnswerKey::check(ItemType::Fill, $fill, 'have workd');
    $translateTypo = AnswerKey::check(ItemType::Translate, $translate, "I've alredy sent my resume.");

    expect($fillTypo->verdict)->toBe(Verdict::Partial)
        ->and($fillTypo->closest)->toBe('have worked')
        ->and($translateTypo->verdict)->toBe(Verdict::Partial);
});

test('a different answer does not match the key', function (string $answer) use ($fill) {
    expect(AnswerKey::check(ItemType::Fill, $fill, $answer)->verdict)->toBe(Verdict::NoMatch);
})->with([
    'work',
    'worked',
    'am working',
    '',
    '   ',
]);

test('a free context item has no key to check', function () {
    expect(AnswerKey::check(ItemType::Context, ['accepted' => []], 'So far, I have visited Rome.')->verdict)->toBe(Verdict::NoMatch);
});

test('the normalizer expands contractions and drops noise', function (string $raw, string $normalized) {
    expect(AnswerNormalizer::normalize($raw))->toBe($normalized);
})->with([
    ["I've", 'i have'],
    ["I don't know.", 'i do not know'],
    ["She can't come!", 'she cannot come'],
    ["We won't go", 'we will not go'],
    ["They're here", 'they are here'],
    ["I'm fine", 'i am fine'],
    ["You'll see", 'you will see'],
    ["Let's go", 'let us go'],
    ['  Mixed   CASE  ', 'mixed case'],
    ['Résumé', 'resume'],
    ['"Quoted."', 'quoted'],
]);
