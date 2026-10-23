<?php

namespace App\Logging;

use Illuminate\Database\QueryException;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

/**
 * Remove dos logs dados sensíveis e conteúdo do aluno (IAS-04, DAD-01).
 *
 * Chaves conhecidas têm o valor trocado por um marcador, e exceções viram
 * só classe, código e local, porque a mensagem pode carregar texto do aluno.
 */
class RedactSensitiveData implements ProcessorInterface
{
    public const REDACTED = '[redacted]';

    /**
     * Palavras que, em qualquer parte do nome da chave, tornam o valor sensível.
     * A comparação é por palavra inteira: "access_token" é sensível, "tokens_used" não.
     *
     * @var list<string>
     */
    private const SENSITIVE_WORDS = [
        'password', 'secret', 'token', 'apikey', 'authorization', 'cookie', 'email',
        'body', 'content', 'text', 'answer', 'material', 'transcript', 'prompt', 'response', 'note',
    ];

    /**
     * Nomes completos de chave sensíveis que não cabem na regra por palavra.
     *
     * @var list<string>
     */
    private const SENSITIVE_KEYS = ['api_key', 'x_api_key'];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->redact($record->context),
            extra: $this->redact($record->extra),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitive($key)) {
                $data[$key] = self::REDACTED;
            } elseif ($value instanceof Throwable) {
                $data[$key] = $this->describe($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }

    private function isSensitive(string $key): bool
    {
        $snake = (string) preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', '_', $key);
        $normalized = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '_', $snake));

        if (in_array($normalized, self::SENSITIVE_KEYS, true)) {
            return true;
        }

        return array_intersect(explode('_', $normalized), self::SENSITIVE_WORDS) !== [];
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(Throwable $exception): array
    {
        return array_filter([
            'class' => $exception::class,
            'code' => $exception->getCode(),
            'file' => $exception->getFile().':'.$exception->getLine(),
            // A SQL sem os valores ligados ajuda a depurar sem expor dados.
            'sql' => $exception instanceof QueryException ? $exception->getSql() : null,
            'previous' => $exception->getPrevious() ? $exception->getPrevious()::class : null,
        ], fn ($value) => $value !== null);
    }
}
