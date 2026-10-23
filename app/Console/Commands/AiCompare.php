<?php

namespace App\Console\Commands;

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Exceptions\AiOutputInvalidException;
use App\Domain\Ai\Exceptions\AiProviderException;
use App\Domain\Ai\Prompts\PromptLibrary;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Ai\Providers\GeminiProvider;
use App\Domain\Ai\Schemas\LessonReviewSchema;
use App\Domain\Ai\StructuredGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\File;

#[Signature('ai:compare {file : Arquivo de texto da aula, em tests/aulas-reais}
    {--with=* : Provedores no formato provedor:modelo, ex.: gemini:gemini-3.8-flash}')]
#[Description('Gera a revisão da mesma aula em vários provedores e grava lado a lado (J2-B13, só local)')]
class AiCompare extends Command
{
    public function handle(PromptLibrary $prompts, Http $http): int
    {
        if (! app()->isLocal()) {
            $this->error('ai:compare só roda no ambiente local.');

            return self::FAILURE;
        }

        $file = (string) $this->argument('file');
        $material = is_file($file) ? (string) file_get_contents($file) : null;

        if ($material === null || trim($material) === '') {
            $this->error('Arquivo não encontrado ou vazio.');

            return self::FAILURE;
        }

        $targets = $this->option('with') ?: ['gemini:'.config('services.gemini.models.strong'), 'gemini:'.config('services.gemini.models.light')];
        $outputDir = storage_path('app/private/ai-compare/'.now()->format('Ymd-His').'-'.pathinfo($file, PATHINFO_FILENAME));
        File::ensureDirectoryExists($outputDir);

        $rows = [];

        foreach ($targets as $target) {
            [$name, $model] = array_pad(explode(':', $target, 2), 2, '');
            $provider = $this->provider($name, $model, $http);
            $generator = new StructuredGenerator($provider, $prompts);
            $started = microtime(true);

            try {
                $response = $generator->generate(AiTask::LessonReview, new LessonReviewSchema, $material, ['target_language' => 'inglês']);
                $seconds = round(microtime(true) - $started, 1);
                File::put("{$outputDir}/".str_replace([':', '/'], '_', $target).'.json', (string) json_encode($response->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $rows[] = [$target, 'ok', $seconds.'s', $response->inputTokens, $response->outputTokens];
            } catch (AiProviderException|AiOutputInvalidException $exception) {
                $rows[] = [$target, $exception->getMessage(), round(microtime(true) - $started, 1).'s', '-', '-'];
            }
        }

        $this->table(['Provedor', 'Resultado', 'Tempo', 'Tokens entrada', 'Tokens saída'], $rows);
        $this->info("Saídas gravadas em {$outputDir}. Compare os arquivos .json lado a lado.");

        return self::SUCCESS;
    }

    private function provider(string $name, string $model, Http $http): AiProvider
    {
        return match ($name) {
            'gemini' => new GeminiProvider(
                http: $http,
                apiKey: (string) config('services.gemini.key'),
                baseUrl: (string) config('services.gemini.url'),
                strongModel: $model,
                lightModel: $model,
                timeoutSeconds: (int) config('services.gemini.timeout'),
            ),
            'fake' => app(FakeAiProvider::class),
            default => throw new \InvalidArgumentException("Provedor desconhecido: {$name}"),
        };
    }
}
