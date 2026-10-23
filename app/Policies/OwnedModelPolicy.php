<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy base para dados de conteúdo: só o dono vê, altera ou apaga (ACE-04).
 * O administrador não ganha acesso ao conteúdo do aluno por padrão.
 *
 * As Policies de cada modelo estendem esta classe e acrescentam regras próprias.
 */
abstract class OwnedModelPolicy
{
    public function view(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    protected function owns(User $user, Model $model): bool
    {
        return (int) $model->getAttribute('user_id') === (int) $user->getKey();
    }
}
