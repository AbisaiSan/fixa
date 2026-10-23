<?php

namespace Tests\Fixtures;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo só de teste para exercitar BelongsToUser e HasPublicUlid.
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property string $body
 */
class OwnedNote extends Model
{
    use BelongsToUser, HasPublicUlid;

    protected $table = 'owned_notes';

    protected $fillable = ['body'];
}
