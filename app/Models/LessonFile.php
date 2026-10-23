<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Arquivo original de uma aula (UPL-02): nome aleatório, disco privado.
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int $lesson_id
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property int|null $pages
 * @property int $position
 * @property string $sha256
 * @property Carbon|null $scanned_at
 */
#[Hidden(['path', 'sha256'])]
class LessonFile extends Model
{
    use BelongsToUser, HasPublicUlid;

    public const DISK = 'lessons';

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'pages' => 'integer',
            'position' => 'integer',
            'scanned_at' => 'datetime',
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
