<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Storage;

/**
 * @property array<int, mixed>|null $includes
 * @property array<int, mixed>|null $details
 */
#[Fillable([
    'category_id', 'name', 'slug', 'sku', 'unit', 'price', 'compare_price', 'cost',
    'rating', 'reviews_count', 'badge', 'art', 'image', 'stock', 'low_stock_threshold',
    'storage_location', 'supplier_id',
    'weight', 'status', 'featured', 'short', 'description', 'includes', 'details',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'includes' => 'array',
            'details' => 'array',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** @return BelongsToMany<Occasion, $this, Pivot, 'pivot'> */
    public function occasions(): BelongsToMany
    {
        return $this->belongsToMany(Occasion::class, 'product_occasion');
    }

    /**
     * @return array{id: int, name: string, slug: string, sku: string, unit: string, storageLocation: string|null, supplier_id: int|null, supplier: string|null, category_id: int, category: string, categorySlug: string, price: float|int, comparePrice: float|int|null, cost: float|int|null, rating: float, reviews: int, badge: string|null, art: string|null, image: string|null, stock: int, lowStock: int, weight: int, status: string, featured: bool, occasions: array<int, string>, short: string|null, description: string|null, includes: array<int, mixed>, details: array<int, mixed>}
     */
    public function toCatalog(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'unit' => $this->unit,
            'storageLocation' => $this->storage_location,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->supplier?->name,
            'category_id' => $this->category_id,
            'category' => $this->category->name,
            'categorySlug' => $this->category->slug,
            'price' => $this->price,
            'comparePrice' => $this->compare_price,
            'cost' => $this->cost,
            'rating' => (float) $this->rating,
            'reviews' => $this->reviews_count,
            'badge' => $this->badge,
            'art' => $this->art,
            'image' => $this->imageUrl(),
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

    private function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        // Seeded/legacy products point at static files served directly from public/ (e.g. /images/catalog/x.jpg).
        // Only paths uploaded via the admin (relative, no leading slash) live on the "public" storage disk.
        return str_starts_with($this->image, '/') ? $this->image : Storage::disk('public')->url($this->image);
    }
}
