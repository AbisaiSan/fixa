<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Aceite de um documento legal numa versão, com data e IP (J1-B7, DAD-01).
 *
 * @property int $id
 * @property int $user_id
 * @property string $document
 * @property string $version
 * @property Carbon $accepted_at
 * @property string|null $ip_address
 */
class Consent extends Model
{
    use BelongsToUser;

    public const DOCUMENTS = ['terms', 'privacy'];

    public $timestamps = false;

    protected $fillable = ['document', 'version', 'accepted_at', 'ip_address'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    /**
     * Registra o aceite das versões vigentes de todos os documentos.
     */
    public static function acceptCurrent(User $user, ?string $ip): void
    {
        foreach (self::DOCUMENTS as $document) {
            $user->consents()->firstOrCreate(
                ['document' => $document, 'version' => config("fixa.legal.{$document}")],
                ['accepted_at' => now(), 'ip_address' => $ip],
            );
        }
    }
}
