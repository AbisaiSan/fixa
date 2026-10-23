<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Scopes\OwnedByAuthenticatedUser;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
 * @property string $theme
 * @property bool $notify_daily_reminder
 * @property bool $notify_weekly_summary
 * @property bool $notify_streak
 * @property-read Enrollment|null $activeEnrollment
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
        'theme' => 'system',
    ];

    /**
     * Matrículas do aluno. A relação já filtra pelo dono, então dispensa o
     * escopo do aluno autenticado (útil no cadastro, em filas e comandos).
     *
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class)->withoutGlobalScope(OwnedByAuthenticatedUser::class);
    }

    /**
     * @return HasOne<Enrollment, $this>
     */
    public function activeEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)
            ->withoutGlobalScope(OwnedByAuthenticatedUser::class)
            ->where('active', true)
            ->latest('id');
    }

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
            'notify_daily_reminder' => 'boolean',
            'notify_weekly_summary' => 'boolean',
            'notify_streak' => 'boolean',
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
