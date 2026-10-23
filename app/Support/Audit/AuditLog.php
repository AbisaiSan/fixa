<?php

namespace App\Support\Audit;

use App\Logging\RedactSensitiveData;
use App\Models\AuditLogEntry;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Trilha de auditoria de ações sensíveis: login, exclusão, exportação e
 * ações de administrador (OPE-03). Grava só identificadores e metadados,
 * nunca conteúdo do aluno, na tabela audit_logs e no canal "audit".
 */
class AuditLog
{
    /**
     * @param  string  $action  Nome da ação em inglês, como "auth.login" ou "account.deleted".
     * @param  array<string, string|int|float|bool|null>  $metadata
     */
    public function record(string $action, ?User $actor = null, array $metadata = []): void
    {
        $ip = request()->ip();

        AuditLogEntry::create([
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'ip_address' => $ip,
            'metadata' => $metadata === [] ? null : $this->redact($metadata),
        ]);

        Log::channel('audit')->info($action, [
            'action' => $action,
            'actor' => $actor?->ulid,
            'ip' => $ip,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mesma regra de remoção dos logs, aplicada antes de gravar no banco.
     *
     * @param  array<string, string|int|float|bool|null>  $metadata
     * @return array<string, string|int|float|bool|null>
     */
    private function redact(array $metadata): array
    {
        $record = new LogRecord(now()->toDateTimeImmutable(), 'audit', Level::Info, '', $metadata);

        /** @var array<string, string|int|float|bool|null> */
        return (new RedactSensitiveData)($record)->context;
    }

    /**
     * Identifica tentativas pelo e-mail sem guardar o e-mail.
     */
    public static function emailFingerprint(?string $email): ?string
    {
        return $email === null || $email === ''
            ? null
            : substr(hash_hmac('sha256', strtolower(trim($email)), (string) config('app.key')), 0, 16);
    }
}
