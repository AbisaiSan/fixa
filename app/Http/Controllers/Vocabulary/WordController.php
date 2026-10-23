<?php

namespace App\Http\Controllers\Vocabulary;

use App\Domain\Vocabulary\AddOwnWord;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Vocabulário do aluno (J3-B8).
 */
class WordController extends Controller
{
    public function store(Request $request, AddOwnWord $addWord): RedirectResponse
    {
        $validated = $request->validate([
            'term' => ['required', 'string', 'max:120'],
            'translation' => ['nullable', 'string', 'max:200'],
            'part_of_speech' => ['nullable', 'string', 'max:30'],
            'definition' => ['nullable', 'string', 'max:300'],
            'fill_with_ai' => ['boolean'],
        ], attributes: ['term' => 'palavra', 'translation' => 'tradução', 'part_of_speech' => 'classe', 'definition' => 'definição']);

        /** @var User $user */
        $user = $request->user();
        $addWord->handle($user, $validated, $request->boolean('fill_with_ai'));

        return back()->with('status', 'word-added');
    }
}
