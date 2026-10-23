<?php

namespace App\Domain\Ai\Prompts;

use App\Domain\Ai\AiTask;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Prompts versionados em resources/prompts/{tarefa}/{versão}.md (J2-B7).
 *
 * O material do aluno vai num bloco com marca aleatória por chamada, citada
 * nas instruções. Assim o texto do aluno não consegue fechar o bloco e se
 * passar por instrução (IAS-01).
 */
final class PromptLibrary
{
    public function __construct(private string $path) {}

    /**
     * @param  array<string, string>  $variables
     */
    public function build(AiTask $task, string $material, array $variables = [], ?string $version = null): Prompt
    {
        $version ??= $this->currentVersion($task);
        $file = "{$this->path}/{$task->value}/{$version}.md";

        if (! is_file($file)) {
            throw new RuntimeException("Prompt [{$task->value}/{$version}] not found.");
        }

        $tag = 'material-'.Str::lower(Str::random(12));

        $instructions = strtr((string) file_get_contents($file), [
            ...collect($variables)->mapWithKeys(fn (string $value, string $key) => ["{{{$key}}}" => $value])->all(),
            '{{material_tag}}' => $tag,
        ]);

        return new Prompt(
            task: $task,
            version: $version,
            instructions: trim($instructions),
            material: "<{$tag}>\n".$this->clean($material)."\n</{$tag}>",
        );
    }

    public function currentVersion(AiTask $task): string
    {
        return (string) config("fixa.ai.prompts.{$task->value}", 'v1');
    }

    /**
     * Tira caracteres de controle invisíveis, que não servem a uma aula e
     * podem esconder instruções.
     */
    private function clean(string $material): string
    {
        $material = (string) preg_replace('/[^\P{C}\n\t]/u', '', $material);

        return trim(Str::of($material)->replace("\r\n", "\n")->value());
    }
}
