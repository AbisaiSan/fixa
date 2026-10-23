<?php

namespace App\Support\Audit;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Trilha de auditoria de ações sensíveis: login, exclusão, exportação e
 * ações de administrador (OPE-03). Grava só identificadores e metadados,
 * nunca conteúdo do aluno.
 */
class AuditLog
{
    /**
     * @param  string  $action  Nome da ação em inglês, como "auth.login" ou "account.deleted".
     * @param  array<string, string|int|float|bool|null>  $metadata
     */
    public function record(string $action, ?User $actor = null, array $metadata = []): void
    {
        Log::channel('audit')->info($action, [
            'action' => $action,
            'actor' => $actor?->ulid,
            'ip' => request()->ip(),
            'metadata' => $metadata,
        ]);
    }
}
