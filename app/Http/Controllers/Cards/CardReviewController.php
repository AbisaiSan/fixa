<?php

namespace App\Http\Controllers\Cards;

use App\Domain\Cards\ReviewCard;
use App\Domain\Cards\Scheduling\Rating;
use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Resposta a um cartão durante a sessão (J3-B5). Responde em JSON, sem
 * recarregar a página, para a sessão seguir também com conexão instável.
 */
class CardReviewController extends Controller
{
    public function store(Request $request, Card $card, ReviewCard $review): JsonResponse
    {
        Gate::authorize('update', $card);

        $validated = $request->validate([
            'rating' => ['required', 'integer', Rule::enum(Rating::class)],
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
            'client_review_id' => ['nullable', 'uuid'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $saved = $review->handle(
            $user,
            $card,
            Rating::from((int) $validated['rating']),
            isset($validated['duration_ms']) ? (int) $validated['duration_ms'] : null,
            $validated['client_review_id'] ?? null,
        );

        $card->refresh();

        return response()->json([
            'card' => $card->ulid,
            'interval_days' => $saved->interval_after,
            'due_at' => $card->due_at?->toIso8601String(),
        ]);
    }
}
