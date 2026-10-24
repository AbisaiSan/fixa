<?php

namespace App\Http\Controllers\Plan;

use App\Domain\Plan\TodayPlan;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Próximo bloco ao fim de uma sessão (J5-F2, fluxo B), sem voltar à home.
 * Também diz se a meta foi cumprida agora, para a comemoração (J5-F3).
 */
class NextBlockController extends Controller
{
    public const CURRENT = ['flashcards', 'writing', 'reading', 'vocabulary', 'weak_point'];

    public function show(Request $request, TodayPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'atual' => ['nullable', 'string', Rule::in(self::CURRENT)],
        ]);

        /** @var User $user */
        $user = $request->user();
        $today = $plan->for($user);
        $current = $validated['atual'] ?? null;

        $next = null;
        foreach ($today['blocks'] ?? [] as $block) {
            if (! $block['done'] && ! $block['skipped'] && $block['href'] !== null && ($block['activity'] ?? $block['kind']) !== $current) {
                $next = $block;
                break;
            }
        }

        return response()->json([
            'date' => $today['date'] ?? null,
            'next' => $next,
            'goal' => $today['goal'] ?? null,
            'streak' => ['current' => $today['streak']['current'] ?? 0],
            'xp' => $today['xp'] ?? null,
        ]);
    }
}
