<?php

namespace App\Http\Controllers;

use App\Models\Consent;
use App\Models\User;
use App\Support\Audit\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function terms(): Response
    {
        return $this->document('terms');
    }

    public function privacy(): Response
    {
        return $this->document('privacy');
    }

    /**
     * Pede novo aceite quando a versão de um documento muda.
     */
    public function edit(): Response
    {
        return Inertia::render('legal/Accept', [
            'versions' => config('fixa.legal'),
        ]);
    }

    public function store(Request $request, AuditLog $audit): RedirectResponse
    {
        $request->validate(['terms' => ['accepted']]);

        /** @var User $user */
        $user = $request->user();
        Consent::acceptCurrent($user, $request->ip());
        $audit->record('legal.accepted', $user, config('fixa.legal'));

        return redirect()->intended(config('fortify.home'));
    }

    private function document(string $name): Response
    {
        /** @var array{title: string, draft: bool, sections: list<array{title: string, paragraphs: list<string>}>} $content */
        $content = require resource_path("legal/{$name}.php");

        return Inertia::render('legal/Document', [
            ...$content,
            'version' => config("fixa.legal.{$name}"),
        ]);
    }
}
