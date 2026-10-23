<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Restringe as consultas aos registros do aluno autenticado (ACE-04).
 * Sem aluno autenticado, nenhuma linha é retornada.
 *
 * @template TModel of Model
 *
 * @implements Scope<TModel>
 */
class OwnedByAuthenticatedUser implements Scope
{
    /**
     * @param  Builder<covariant TModel>  $builder
     * @param  TModel  $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('user_id'), $userId);
    }
}
