<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'slug', 'art', 'image', 'tagline', 'product_count'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @param Builder<Category> $query */
    public function scopeWithCountActiveProducts(Builder $query): Builder
    {
        return $query->withCount(['products as products_active_count' => fn ($q) => $q->where('status', 'active')]);
    }

    /**
     * @return array{id: int, name: string, slug: string, art: string|null, image: string|null, tagline: string|null, count: int|null}
     */
    public function toCatalog(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'art' => $this->art,
            'image' => $this->imageUrl(),
            'tagline' => $this->tagline,
            'count' => $this->products_active_count ?? $this->product_count,
        ];
    }

    private function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        // Seeded/legacy categories point at static files served directly from public/ (e.g. /images/catalog/x.jpg).
        // Only paths uploaded via the admin (relative, no leading slash) live on the "public" storage disk.
        return str_starts_with($this->image, '/') ? $this->image : Storage::disk('public')->url($this->image);
    }
}
