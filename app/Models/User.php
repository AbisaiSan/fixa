<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\HasPublicUlid;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property string $email
 * @property Role $role
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPublicUlid, Notifiable, TwoFactorAuthenticatable;

    /**
     * Toda conta nasce aluno; só o comando fixa:admin promove.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'student',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => Role::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Dados do aluno que o navegador pode ver: nunca o id numérico nem segredos.
     *
     * @return array{ulid: string, name: string, email: string, email_verified: bool, two_factor_enabled: bool, is_admin: bool}
     */
    public function toPublicArray(): array
    {
        return [
            'ulid' => $this->ulid,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at !== null,
            'two_factor_enabled' => $this->hasTwoFactorEnabled(),
            'is_admin' => $this->isAdmin(),
        ];
    }
}
