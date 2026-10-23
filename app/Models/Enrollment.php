<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Matrícula: um aluno estudando um idioma (J1-B6). Todo dado de conteúdo
 * pertence a uma matrícula, e o "dia" do aluno é calculado no fuso dela (GAM-06).
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property string $target_language
 * @property string $instruction_language
 * @property string|null $level
 * @property int $daily_goal_minutes
 * @property string $timezone
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['target_language', 'level', 'daily_goal_minutes', 'timezone'])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use BelongsToUser, HasFactory, HasPublicUlid;

    public const MIN_DAILY_GOAL = 5;

    public const MAX_DAILY_GOAL = 30;

    public const DEFAULT_DAILY_GOAL = 10;

    public const DEFAULT_TIMEZONE = 'America/Sao_Paulo';

    /** @var array<string, mixed> */
    protected $attributes = [
        'instruction_language' => 'pt-BR',
        'daily_goal_minutes' => self::DEFAULT_DAILY_GOAL,
        'timezone' => self::DEFAULT_TIMEZONE,
        'active' => true,
    ];

    protected function casts(): array
    {
        return [
            'daily_goal_minutes' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Data de hoje no fuso do aluno.
     */
    public function today(): Carbon
    {
        return Carbon::now($this->timezone)->startOfDay();
    }
}
