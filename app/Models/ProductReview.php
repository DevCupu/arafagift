<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ulasan produk dari pembeli. Disimpan sebagai 'pending' dulu dan baru
 * tampil di storefront setelah admin menyetujui.
 */
#[Fillable([
    'product_id', 'user_id', 'order_id', 'author_name', 'rating', 'title',
    'body', 'status', 'admin_note', 'moderated_by', 'moderated_at',
])]
class ProductReview extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /** @var list<string> */
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'moderated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /** @param  Builder<self>  $query */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Versi publik untuk storefront: tanpa catatan admin, email, atau nomor pesanan.
     *
     * @return array{id: int, rating: int, title: string|null, body: string, author: string, createdAt: string|null}
     */
    public function toPublic(): array
    {
        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'title' => $this->title,
            'body' => $this->body,
            'author' => $this->author_name,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{id: int, rating: int, title: string|null, body: string, author: string, status: string, adminNote: string|null, verified: bool, createdAt: string|null, product: array{id: int, name: string, slug: string}|null, orderNumber: string|null, email: string|null, moderator: string|null, moderatedAt: string|null}
     */
    public function toCatalog(): array
    {
        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'title' => $this->title,
            'body' => $this->body,
            'author' => $this->author_name,
            'status' => $this->status,
            'adminNote' => $this->admin_note,
            'verified' => $this->order_id !== null,
            'createdAt' => $this->created_at?->toIso8601String(),
            'product' => $this->relationLoaded('product') && $this->product
                ? [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'slug' => $this->product->slug,
                ]
                : null,
            'orderNumber' => $this->relationLoaded('order') ? $this->order?->order_number : null,
            'email' => $this->relationLoaded('user') ? $this->user?->email : null,
            'moderator' => $this->relationLoaded('moderator') ? $this->moderator?->name : null,
            'moderatedAt' => $this->moderated_at?->toIso8601String(),
        ];
    }
}
