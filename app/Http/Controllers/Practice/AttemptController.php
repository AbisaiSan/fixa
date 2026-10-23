<?php

namespace App\Http\Controllers\Practice;

use App\Domain\Practice\AnswerItem;
use App\Domain\Practice\CorrectionUnavailable;
use App\Domain\Practice\DailyLimitReached;
use App\Http\Controllers\Controller;
use App\Models\PracticeItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Resposta a um item de prática (J4-B4). JSON, sem recarregar a sessão.
 * A correção vem como dado e a tela sempre a exibe escapada (IAS-02).
 */
class AttemptController extends Controller
{
    public function store(Request $request, PracticeItem $item, AnswerItem $answerItem): JsonResponse
    {
        Gate::authorize('update', $item);

        $validated = $request->validate([
            'answer' => ['required', 'string', 'max:500'],
        ], attributes: ['answer' => 'resposta']);

        /** @var User $user */
        $user = $request->user();

        try {
            $attempt = $answerItem->handle($user, $item, $validated['answer']);
        } catch (DailyLimitReached) {
            return response()->json(['error' => 'ai_limit_reached'], 429);
        } catch (CorrectionUnavailable) {
            return response()->json(['error' => 'correction_unavailable'], 503);
        }

        return response()->json([
            'item' => $item->ulid,
            'verdict' => $attempt->verdict->value,
            'xp' => $attempt->xp,
            'correction' => $attempt->correction,
        ]);
    }
}
