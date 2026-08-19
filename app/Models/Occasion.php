<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['slug', 'title', 'note', 'art'])]
class Occasion extends Model
{
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_occasion');
    }

    public function toCatalog(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'note' => $this->note,
            'art' => $this->art,
        ];
    }
}
