<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Evento de segurança, sem conteúdo do aluno (J1-B8, OPE-03, IAS-04).
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $ip_address
 * @property array<string, string|int|float|bool|null>|null $metadata
 * @property Carbon $created_at
 */
class AuditLogEntry extends Model
{
    protected $table = 'audit_logs';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'ip_address', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
