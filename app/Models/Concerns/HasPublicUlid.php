<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Identificador público em ULID, usado em rotas e na interface.
 * O id numérico continua interno e nunca sai do servidor.
 *
 * @mixin Model
 */
trait HasPublicUlid
{
    public static function bootHasPublicUlid(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('ulid'))) {
                $model->setAttribute('ulid', strtolower((string) Str::ulid()));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
