<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property array<string, mixed> $data
 */
#[Fillable(['key', 'data'])]
class Content extends Model
{
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
