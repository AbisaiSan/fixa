<?php

namespace App\Domain\Practice;

use App\Domain\Ai\AiCallContext;
use App\Domain\Ai\AiTask;
use App\Domain\Ai\DailyAiAllowance;
use App\Domain\Ai\Exceptions\AiOutputInvalidException;
use App\Domain\Ai\Exceptions\AiProviderException;
use App\Domain\Ai\Schemas\TextCorrectionSchema;
use App\Domain\Ai\StructuredGenerator;
use App\Models\PracticeItem;
use App\Models\User;

/**
 * Corrige a resposta de um item (J4-B2, J4-B3, ATV-02). O gabarito vem
 * primeiro, de graça e na hora. "Completar" fica sempre no gabarito.
 * Traduzir e formar frase, quando não batem, vão para a IA no modelo leve,
 * que aceita respostas certas diferentes do gabarito. O exercício e a
 * resposta do aluno vão como dados, nunca como instrução (IAS-01).
 */
final class ItemCorrector
{
    public function __construct(
        private StructuredGenerator $generator,
        private DailyAiAllowance $allowance,
    ) {}

    /**
     * @throws DailyLimitReached quando a correção precisaria da IA e o limite do dia acabou
     * @throws CorrectionUnavailable quando a IA falhou e não há gabarito
     */
    public function correct(User $user, PracticeItem $item, string $answer): Correction
    {
        $content = $item->content;
        $check = AnswerKey::check($item->type, $content, $answer);

        if ($check->verdict !== Verdict::NoMatch) {
            return $this->fromKey($content, $check, $answer);
        }

        if (! $this->needsAi($item->type)) {
            return $this->wrongByKey($content, $answer);
        }

        if ($this->allowance->left($user) === 0) {
            throw new DailyLimitReached;
        }

        try {
            return $this->byAi($user, $item, $answer);
        } catch (AiOutputInvalidException|AiProviderException) {
            // Inclui o teto mensal. Com gabarito, ainda dá para corrigir.
            if ($item->type->hasAnswerKey()) {
                return $this->wrongByKey($content, $answer);
            }

            throw new CorrectionUnavailable;
        }
    }

    public function needsAi(ItemType $type): bool
    {
        return in_array($type, [ItemType::Translate, ItemType::Context], true);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function fromKey(array $content, AnswerCheck $check, string $answer): Correction
    {
        $partial = $check->verdict === Verdict::Partial;

        return new Correction(
            verdict: $check->verdict,
            explanation: $partial
                ? 'Quase lá. Confira a ortografia: '.$check->closest.'.'
                : (string) ($content['why'] ?? ''),
            corrected: (string) ($content['corrected'] ?? $check->closest ?? ''),
            natural: (string) ($content['natural'] ?? $content['corrected'] ?? ''),
            error: $partial ? trim($answer) : null,
            category: $partial ? ErrorCategory::Spelling : null,
            point: $partial ? (string) ($content['point'] ?? '') : null,
        );
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function wrongByKey(array $content, string $answer): Correction
    {
        return new Correction(
            verdict: Verdict::Wrong,
            explanation: (string) ($content['why'] ?? ''),
            corrected: (string) ($content['corrected'] ?? ''),
            natural: (string) ($content['natural'] ?? ''),
            error: trim($answer),
            // No ponto fraco, o erro volta na categoria em que foi anotado.
            category: ErrorCategory::tryFrom((string) ($content['category'] ?? '')) ?? ErrorCategory::Grammar,
            point: (string) ($content['point'] ?? ''),
        );
    }

    private function byAi(User $user, PracticeItem $item, string $answer): Correction
    {
        $content = $item->content;
        $language = $user->activeEnrollment->target_language ?? (string) config('fixa.target_language');
        $level = $user->activeEnrollment?->level;

        $material = implode("\n", [
            'Exercício: '.($content['instruction'] ?? ''),
            'Frase ou situação: '.($content['sentence'] ?? ''),
            'Resposta modelo: '.($content['corrected'] ?? ''),
            'Resposta do aluno: '.trim($answer),
        ]);

        $response = $this->generator->generate(
            AiTask::TextCorrection,
            new TextCorrectionSchema,
            $material,
            [
                'target_language' => (string) (config("fixa.language_names.{$language}") ?? $language),
                'level' => $level === null ? ActivityGenerator::DEFAULT_LEVEL : strtoupper($level),
            ],
            new AiCallContext(userId: $user->id, lessonId: $item->session?->source_lesson_id),
        );

        $data = $response->data;
        $verdict = Verdict::from($data['verdict']);
        $filled = fn (?string $value) => $value === null || trim($value) === '' ? null : trim($value);

        return new Correction(
            verdict: $verdict,
            explanation: trim($data['explanation']),
            corrected: trim($data['corrected']),
            natural: trim($data['natural']),
            error: $verdict === Verdict::Total ? null : $filled($data['error']),
            category: $verdict === Verdict::Total || $filled($data['category']) === null ? null : ErrorCategory::from($data['category']),
            point: $verdict === Verdict::Total ? null : $filled($data['point']),
            source: Correction::BY_AI,
        );
    }
}
