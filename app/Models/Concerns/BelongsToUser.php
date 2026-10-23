<?php

namespace App\Models\Concerns;

use App\Models\Scopes\OwnedByAuthenticatedUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Todo dado de conteúdo pertence a um aluno (ACE-04).
 *
 * As consultas são filtradas pelo aluno autenticado e falham fechadas sem
 * autenticação. Fora de uma requisição (filas, comandos), use forUser().
 *
 * @mixin Model
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope(new OwnedByAuthenticatedUser);

        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('user_id'))) {
                $model->setAttribute('user_id', Auth::id());
            }

            if (empty($model->getAttribute('user_id'))) {
                throw new LogicException('A '.class_basename($model).' must belong to a user.');
            }
        });
    }

    /**
     * Consulta explícita pelos registros de um aluno, sem depender da autenticação.
     *
     * @return Builder<static>
     */
    public static function forUser(User $user): Builder
    {
        $query = static::query()->withoutGlobalScope(OwnedByAuthenticatedUser::class);

        return $query->where($query->qualifyColumn('user_id'), $user->getKey());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
