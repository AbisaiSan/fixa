<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fixa:admin {email : E-mail da conta} {--revoke : Volta a conta para aluno}')]
#[Description('Torna uma conta administradora, ou revoga, pelo terminal (J1-B5)')]
class SetAdminRole extends Command
{
    public function handle(): int
    {
        $user = User::where('email', strtolower((string) $this->argument('email')))->first();

        if ($user === null) {
            $this->error('Conta não encontrada.');

            return self::FAILURE;
        }

        $user->role = $this->option('revoke') ? Role::Student : Role::Admin;
        $user->save();

        $this->info($user->isAdmin()
            ? 'Conta agora é administradora. Ela precisa ativar o 2FA no próximo acesso.'
            : 'Conta voltou a ser de aluno.');

        return self::SUCCESS;
    }
}
