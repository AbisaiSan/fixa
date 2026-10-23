<?php

namespace App\Http\Controllers\Practice;

use App\Domain\Practice\SaveReadingWord;
use App\Domain\Practice\SessionKind;
use App\Http\Controllers\Controller;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * "Salvar no vocabulário" na leitura (J4-B7). JSON, para não perder o texto.
 */
class ReadingWordController extends Controller
{
    public function store(Request $request, PracticeSession $session, SaveReadingWord $save): JsonResponse
    {
        Gate::authorize('update', $session);
        abort_unless($session->kind === SessionKind::Reading, 404);

        $validated = $request->validate(['form' => ['required', 'string', 'max:60']]);

        /** @var User $user */
        $user = $request->user();

        try {
            $word = $save->handle($user, $session, $validated['form']);
        } catch (InvalidArgumentException) {
            return response()->json(['error' => 'not_in_glossary'], 422);
        }

        return response()->json(['word' => $word->ulid, 'term' => $word->term]);
    }
}
