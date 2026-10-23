<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Revisão da aula (REV-01 a REV-04): gerada uma vez e guardada. Guarda a aula
 * de origem, o provedor e a versão do prompt (MAT-03, Modelo de dados).
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int $lesson_id
 * @property string $summary
 * @property list<array{name: string, explanation: string, examples: list<array{target: string, translation: string}>}> $concepts
 * @property list<array{wrong: string, right: string, why: string}> $common_mistakes
 * @property list<string> $can_do
 * @property string $provider
 * @property string $model
 * @property string $prompt_version
 * @property bool $reported
 * @property Carbon|null $created_at
 */
class LessonReview extends Model
{
    use BelongsToUser, HasPublicUlid;

    protected function casts(): array
    {
        return [
            'summary' => 'encrypted',
            'concepts' => 'encrypted:array',
            'common_mistakes' => 'encrypted:array',
            'can_do' => 'encrypted:array',
            'reported' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
