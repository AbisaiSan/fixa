<?php

namespace App\Providers;

use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Prompts\PromptLibrary;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Ai\Providers\GeminiProvider;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

/**
 * Escolhe o provedor de IA pela configuração (FIXA_AI_PROVIDER).
 */
class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FakeAiProvider::class);
        $this->app->singleton(PromptLibrary::class, fn () => new PromptLibrary(resource_path('prompts')));

        $this->app->singleton(AiProvider::class, fn ($app) => match (config('fixa.ai.provider')) {
            'fake' => $app->make(FakeAiProvider::class),
            'gemini' => new GeminiProvider(
                http: $app->make(Http::class),
                apiKey: (string) config('services.gemini.key'),
                baseUrl: (string) config('services.gemini.url'),
                strongModel: (string) config('services.gemini.models.strong'),
                lightModel: (string) config('services.gemini.models.light'),
                timeoutSeconds: (int) config('services.gemini.timeout'),
            ),
            default => throw new InvalidArgumentException('Unknown AI provider ['.config('fixa.ai.provider').'].'),
        });
    }
}
