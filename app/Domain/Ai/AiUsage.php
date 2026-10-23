<?php

namespace App\Domain\Ai;

use App\Domain\Ai\Exceptions\AiBudgetExceeded;
use App\Support\Audit\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Registro de uso e teto mensal de gasto com IA (J2-B12, IAS-03, IAS-04).
 *
 * O custo é estimado pela tabela de preços em config/fixa.php. Ao passar de
 * 80% do teto, registra um alerta na auditoria uma vez por mês; ao atingir
 * o teto, novas chamadas são recusadas.
 */
final class AiUsage
{
    public const ALERT_RATIO = 0.8;

    public function __construct(private AuditLog $audit) {}

    /**
     * @throws AiBudgetExceeded
     */
    public function ensureWithinBudget(): void
    {
        if ($this->spentThisMonth() >= $this->budget()) {
            throw new AiBudgetExceeded;
        }
    }

    public function record(AiTask $task, string $promptVersion, ?AiResponse $response, string $outcome, AiCallContext $context, string $provider): void
    {
        $model = $response->model ?? 'unknown';
        $input = $response->inputTokens ?? 0;
        $output = $response->outputTokens ?? 0;

        DB::table('ai_calls')->insert([
            'user_id' => $context->userId,
            'lesson_id' => $context->lessonId,
            'provider' => $provider,
            'model' => $model,
            'task' => $task->value,
            'prompt_version' => $promptVersion,
            'outcome' => $outcome,
            'input_tokens' => $input,
            'output_tokens' => $output,
            'cost_usd' => $this->cost($model, $input, $output),
            'created_at' => now(),
        ]);

        $this->alertIfNeeded();
    }

    public function spentThisMonth(): float
    {
        return (float) DB::table('ai_calls')->where('created_at', '>=', now()->startOfMonth())->sum('cost_usd');
    }

    public function budget(): float
    {
        return (float) config('fixa.ai.monthly_budget_usd');
    }

    public function cost(string $model, int $inputTokens, int $outputTokens): float
    {
        /** @var array{input?: float|int, output?: float|int} $price */
        $price = config("fixa.ai.prices.{$model}", []);

        return round(($inputTokens * (float) ($price['input'] ?? 0) + $outputTokens * (float) ($price['output'] ?? 0)) / 1_000_000, 6);
    }

    private function alertIfNeeded(): void
    {
        $key = 'ai-budget-alert-'.now()->format('Y-m');

        if ($this->spentThisMonth() >= $this->budget() * self::ALERT_RATIO && Cache::add($key, true, now()->endOfMonth())) {
            $this->audit->record('ai.budget_alert', null, [
                'spent_usd' => round($this->spentThisMonth(), 2),
                'budget_usd' => $this->budget(),
            ]);
        }
    }
}
