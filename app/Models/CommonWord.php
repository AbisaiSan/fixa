<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Palavra frequente do idioma (VOC-04). Não pertence a um aluno: é a mesma
 * lista para todos e nunca guarda dado de aluno. Carga na jornada 7.
 *
 * @property int $id
 * @property string $language
 * @property string $term
 * @property int $rank
 * @property string|null $part_of_speech
 * @property string $source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CommonWord extends Model
{
    protected function casts(): array
    {
        return ['rank' => 'integer'];
    }
}
