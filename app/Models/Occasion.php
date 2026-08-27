<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['slug', 'title', 'note', 'art'])]
class Occasion extends Model
{
    /** @return BelongsToMany<Product, $this, Pivot, 'pivot'> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_occasion');
    }

    /**
     * @return array{slug: string, title: string, note: string|null, art: string|null}
     */
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
