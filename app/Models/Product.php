<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'category_id', 'name', 'slug', 'sku', 'price', 'compare_price', 'cost',
    'rating', 'reviews_count', 'badge', 'art', 'image', 'stock', 'low_stock_threshold',
    'weight', 'status', 'featured', 'short', 'description', 'includes', 'details',
])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'includes' => 'array',
            'details' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function occasions(): BelongsToMany
    {
        return $this->belongsToMany(Occasion::class, 'product_occasion');
    }

    public function toCatalog(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'category' => $this->category->name,
            'categorySlug' => $this->category->slug,
            'price' => $this->price,
            'comparePrice' => $this->compare_price,
            'cost' => $this->cost,
            'rating' => (float) $this->rating,
            'reviews' => $this->reviews_count,
            'badge' => $this->badge,
            'art' => $this->art,
            'image' => $this->image,
            'stock' => $this->stock,
            'lowStock' => $this->low_stock_threshold,
            'weight' => $this->weight,
            'status' => $this->status,
            'featured' => $this->featured,
            'occasions' => $this->occasions->pluck('slug')->all(),
            'short' => $this->short,
            'description' => $this->description,
            'includes' => $this->includes ?? [],
            'details' => $this->details ?? [],
        ];
    }
}
