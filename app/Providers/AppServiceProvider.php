<?php

namespace App\Providers;

use App\Domain\Cards\Scheduling\ReviewScheduler;
use App\Domain\Cards\Scheduling\Sm2Scheduler;
use App\Listeners\RecordAuthenticationEvents;
use App\Models\Card;
use App\Models\LessonReview;
use App\Models\PracticeItem;
use App\Models\Word;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Algoritmo de repetição espaçada (FLA-03). Trocar é mudar esta linha.
        $this->app->bind(ReviewScheduler::class, Sm2Scheduler::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Event::subscribe(RecordAuthenticationEvents::class);

        // Nome estável dos itens reportáveis no banco, sem depender da classe (MAT-05).
        Relation::morphMap(['lesson_review' => LessonReview::class, 'card' => Card::class, 'word' => Word::class, 'practice_item' => PracticeItem::class]);

        // Envio de aulas (ACE-05): evita abuso de armazenamento e de IA.
        RateLimiter::for('lesson-uploads', fn (Request $request) => [
            Limit::perMinute(5)->by('upload|'.$request->user()?->getKey()),
            Limit::perDay(30)->by('upload-day|'.$request->user()?->getKey()),
        ]);

        // Respostas aos cartões: uma sessão rápida tem algumas por minuto (ACE-05).
        RateLimiter::for('card-reviews', fn (Request $request) => Limit::perMinute(120)->by('card-review|'.$request->user()?->getKey()));

        // Palavras adicionadas pelo aluno, que podem chamar a IA (ATV-05).
        RateLimiter::for('word-additions', fn (Request $request) => Limit::perMinute(10)->by('word-add|'.$request->user()?->getKey()));

        // Nova geração e "tentar de novo" chamam a IA (REV-04, IAS-03).
        RateLimiter::for('lesson-generations', fn (Request $request) => [
            Limit::perMinute(3)->by('generation|'.$request->user()?->getKey()),
            Limit::perDay(20)->by('generation-day|'.$request->user()?->getKey()),
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Regra única em todo ambiente, como o protótipo informa ao aluno (ACE-02).
        // Em produção, também recusa senhas que apareceram em vazamentos.
        Password::defaults(function (): Password {
            $rule = Password::min(8)->letters()->numbers();

            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}
