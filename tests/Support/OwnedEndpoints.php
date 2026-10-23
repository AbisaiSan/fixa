<?php

namespace Tests\Support;

use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\OwnedNote;
use Tests\Fixtures\OwnedNotePolicy;

/**
 * Matriz de acesso entre alunos (J1-B9, ACE-04).
 *
 * Todo endpoint que recebe um dado de aluno entra em all(): o teste
 * CrossStudentAccessTest tenta ler, alterar e apagar o dado do aluno A
 * logado como o aluno B, e exige que nada aconteça. Uma rota nova com
 * modelo que usa BelongsToUser e fica fora daqui faz o teste falhar.
 */
final class OwnedEndpoints
{
    /**
     * @return array<string, array{method: string, route: string, make: Closure(User): Model, payload: array<string, mixed>}>
     */
    public static function all(): array
    {
        return [
            // Exemplo com o modelo de teste; troque pelos reais a partir da J2.
            'fixture: ler nota' => self::endpoint('get', 'test.notes.show', self::makeNote(...)),
            'fixture: alterar nota' => self::endpoint('patch', 'test.notes.update', self::makeNote(...), ['body' => 'invadido']),
            'fixture: apagar nota' => self::endpoint('delete', 'test.notes.destroy', self::makeNote(...)),
        ];
    }

    /**
     * Nomes de rota cobertos pela matriz.
     *
     * @return list<string>
     */
    public static function routeNames(): array
    {
        return array_values(array_unique(array_column(self::all(), 'route')));
    }

    /**
     * Prepara o que os exemplos da matriz precisam: tabela, Policy e rotas de teste.
     */
    public static function bootFixtures(): void
    {
        Schema::create('owned_notes', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Gate::policy(OwnedNote::class, OwnedNotePolicy::class);

        Route::middleware(['web', 'auth'])->prefix('_test/notes')->name('test.notes.')->group(function () {
            Route::get('{note}', function (OwnedNote $note) {
                Gate::authorize('view', $note);

                return $note->body;
            })->name('show');

            Route::patch('{note}', function (OwnedNote $note) {
                Gate::authorize('update', $note);
                $note->update(['body' => request('body')]);

                return 'ok';
            })->name('update');

            Route::delete('{note}', function (OwnedNote $note) {
                Gate::authorize('delete', $note);
                $note->delete();

                return 'ok';
            })->name('destroy');
        });

        app('router')->getRoutes()->refreshNameLookups();
    }

    /**
     * @param  Closure(User): Model  $make
     * @param  array<string, mixed>  $payload
     * @return array{method: string, route: string, make: Closure(User): Model, payload: array<string, mixed>}
     */
    private static function endpoint(string $method, string $route, Closure $make, array $payload = []): array
    {
        return compact('method', 'route', 'make', 'payload');
    }

    private static function makeNote(User $owner): OwnedNote
    {
        $note = (new OwnedNote(['body' => 'nota da aluna A']))->forceFill(['user_id' => $owner->id]);
        $note->save();

        return $note;
    }
}
