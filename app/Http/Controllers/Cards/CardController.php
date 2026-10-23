<?php

namespace App\Http\Controllers\Cards;

use App\Domain\Cards\ManageCard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cards\CardRequest;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Criar, editar, suspender, reativar e remover cartão (J3-B6, FLA-06).
 */
class CardController extends Controller
{
    public function store(CardRequest $request, ManageCard $cards): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // A aula, se vier, é procurada só entre as do aluno (ACE-04).
        $lesson = $request->filled('lesson')
            ? Lesson::query()->where('ulid', $request->string('lesson')->value())->firstOrFail()
            : null;

        $cards->create($user, $request->content(), $lesson);

        return back()->with('status', 'card-created');
    }

    public function update(CardRequest $request, Card $card, ManageCard $cards): RedirectResponse
    {
        Gate::authorize('update', $card);

        /** @var User $user */
        $user = $request->user();
        $cards->update($user, $card, $request->content());

        return back()->with('status', 'card-updated');
    }

    public function suspend(Card $card, ManageCard $cards): RedirectResponse
    {
        Gate::authorize('update', $card);
        $cards->suspend($card);

        return back()->with('status', 'card-suspended');
    }

    public function resume(Card $card, ManageCard $cards): RedirectResponse
    {
        Gate::authorize('update', $card);
        $cards->resume($card);

        return back()->with('status', 'card-resumed');
    }

    public function destroy(Card $card, ManageCard $cards): RedirectResponse
    {
        Gate::authorize('delete', $card);
        $cards->delete($card);

        return back()->with('status', 'card-deleted');
    }
}
