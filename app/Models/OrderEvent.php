<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak audit perubahan penting pada pesanan (status, pembayaran, expiry).
 *
 * Ditulis dari service/controller, bukan observer, supaya aksi sistem
 * (expiry otomatis) dan aksi admin sama-sama tercatat dengan konteks yang jelas.
 */
#[Fillable(['order_id', 'type', 'from_value', 'to_value', 'actor_id', 'meta'])]
class OrderEvent extends Model
{
    public const TYPE_ORDER_CREATED = 'order_created';

    public const TYPE_STATUS_CHANGED = 'order_status_changed';

    public const TYPE_PAYMENT_STATUS_CHANGED = 'payment_status_changed';

    public const TYPE_PROOF_SUBMITTED = 'payment_proof_submitted';

    public const TYPE_PAYMENT_VERIFIED = 'payment_verified';

    public const TYPE_PAYMENT_REJECTED = 'payment_rejected';

    public const TYPE_ORDER_EXPIRED = 'order_expired';

    public const TYPE_ORDER_CANCELLED = 'order_cancelled';

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function log(
        Order $order,
        string $type,
        ?string $from = null,
        ?string $to = null,
        ?User $actor = null,
        array $meta = [],
    ): self {
        return self::create([
            'order_id' => $order->id,
            'type' => $type,
            'from_value' => $from,
            'to_value' => $to,
            'actor_id' => $actor?->id,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    public function label(): string
    {
        return match ($this->type) {
            self::TYPE_ORDER_CREATED => 'Pesanan dibuat',
            self::TYPE_STATUS_CHANGED => 'Status pesanan diperbarui',
            self::TYPE_PAYMENT_STATUS_CHANGED => 'Status pembayaran diperbarui',
            self::TYPE_PROOF_SUBMITTED => 'Bukti pembayaran dikirim',
            self::TYPE_PAYMENT_VERIFIED => 'Pembayaran diverifikasi',
            self::TYPE_PAYMENT_REJECTED => 'Bukti pembayaran ditolak',
            self::TYPE_ORDER_EXPIRED => 'Pesanan dibatalkan otomatis',
            self::TYPE_ORDER_CANCELLED => 'Pesanan dibatalkan pelanggan',
            default => $this->type,
        };
    }

    /**
     * @return array{id: int, type: string, label: string, from: string|null, to: string|null, actor: string, meta: array<string, mixed>, at: string|null}
     */
    public function toCatalog(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'label' => $this->label(),
            'from' => $this->from_value,
            'to' => $this->to_value,
            'actor' => $this->actor?->name ?? 'Sistem',
            'meta' => $this->meta ?? [],
            'at' => $this->created_at?->toIso8601String(),
        ];
    }
}
