<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code', 'type', 'usage', 'period', 'status',
    'discount_type', 'discount_value', 'min_purchase', 'max_discount',
    'starts_at', 'ends_at', 'usage_limit', 'used_count', 'applies_to', 'applies_value', 'is_active',
])]
class Promotion extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'min_purchase' => 'integer',
            'max_discount' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
