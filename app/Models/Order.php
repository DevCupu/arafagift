<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'order_number', 'tracking_token', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
    'address', 'city', 'province', 'postal_code', 'shipping_method_id', 'shipping_cost',
    'shipping_destination_id', 'shipping_courier', 'shipping_service', 'shipping_etd',
    'payment_method_id', 'gift_message', 'hide_invoice', 'status', 'payment_status',
    'payment_due_at', 'payment_reminder_sent_at', 'payment_confirmed_at', 'payment_reference',
    'fulfillment_type', 'voucher_code', 'discount_total', 'channel', 'note', 'awb', 'admin_note',
    'subtotal', 'total',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'hide_invoice' => 'boolean',
            'discount_total' => 'integer',
            'shipping_cost' => 'integer',
            'subtotal' => 'integer',
            'total' => 'integer',
            'payment_due_at' => 'datetime',
            'payment_reminder_sent_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
        ];
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ShippingMethod, $this> */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    /** @return BelongsTo<PaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /** @return HasMany<PaymentConfirmation, $this> */
    public function paymentConfirmations(): HasMany
    {
        return $this->hasMany(PaymentConfirmation::class);
    }

    /** @return HasOne<PaymentConfirmation, $this> */
    public function latestPaymentConfirmation(): HasOne
    {
        return $this->hasOne(PaymentConfirmation::class)->latestOfMany();
    }

    /** @return HasMany<OrderEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class);
    }

    /**
     * @return array{id: string, customer: string, email: string, phone: string, date: string, payment: string, status: string, channel: string, note: string|null, address: string, shipping: array{method: string, cost: float|int, courier: string, etd: string|null, awb: string|null}, items: array<int, array{name: string, sku: string, qty: int, price: float|int, art: string}>}
     */
    public function isPickup(): bool
    {
        return $this->fulfillment_type === 'pickup';
    }

    /** Sama dengan syarat di OrderLifecycle::cancelByCustomer(). */
    public function canBeCancelledByCustomer(): bool
    {
        return $this->status === 'pending'
            && in_array($this->payment_status ?? 'unpaid', ['unpaid', 'waiting_confirmation'], true);
    }

    public function toCatalog(): array
    {
        $pickup = $this->isPickup();

        return [
            'id' => $this->order_number,
            'trackingToken' => $this->tracking_token,
            'customer' => $this->customer_name,
            'email' => $this->customer_email,
            'phone' => $this->customer_phone,
            'date' => $this->created_at->toIso8601String(),
            'deletedAt' => $this->deleted_at?->toIso8601String(),
            'payment' => optional($this->paymentMethod)->name ?? 'Belum ditentukan',
            'paymentCode' => optional($this->paymentMethod)->code,
            'paymentNote' => optional($this->paymentMethod)->note,
            'paymentInstructions' => optional($this->paymentMethod)->instructions,
            'paymentStatus' => $this->payment_status ?? 'unpaid',
            'paymentDueAt' => $this->payment_due_at?->toIso8601String(),
            'paymentConfirmedAt' => $this->payment_confirmed_at?->toIso8601String(),
            'paymentReference' => $this->payment_reference,
            // Relasi hanya disertakan kalau memang di-eager-load oleh controller,
            // supaya halaman daftar pesanan tidak ikut menarik semua bukti bayar.
            'paymentProof' => $this->relationLoaded('latestPaymentConfirmation')
                ? $this->latestPaymentConfirmation?->toCatalog()
                : null,
            'paymentProofs' => $this->relationLoaded('paymentConfirmations')
                ? $this->paymentConfirmations->map->toCatalog()->values()->all()
                : [],
            'events' => $this->relationLoaded('events')
                ? $this->events->sortBy('id')->map->toCatalog()->values()->all()
                : [],
            'fulfillment' => $pickup ? 'pickup' : 'delivery',
            'voucherCode' => $this->voucher_code,
            'subtotal' => (int) $this->subtotal,
            'discount' => (int) $this->discount_total,
            'total' => (int) $this->total,
            'status' => $this->status,
            'canCancel' => $this->canBeCancelledByCustomer(),
            'channel' => $this->channel,
            'note' => $this->note,
            'admin_note' => $this->admin_note,
            'address' => $pickup ? '' : trim(implode(', ', array_filter([
                $this->address, $this->city, $this->province,
            ])).' '.$this->postal_code),
            'shipping' => [
                'method' => $pickup ? 'Ambil di Tempat' : ($this->shipping_service ?? 'Belum ditentukan'),
                'cost' => $pickup ? 0 : $this->shipping_cost,
                'courier' => $pickup ? 'Ambil di Toko' : ($this->shipping_courier ?? 'Belum ditentukan'),
                'etd' => $pickup ? null : $this->shipping_etd,
                'awb' => $this->awb,
            ],
            'items' => $this->items->map(fn (OrderItem $item) => [
                'name' => $item->name,
                'sku' => $item->sku,
                'qty' => $item->qty,
                'price' => $item->price,
                'art' => $item->art,
            ])->all(),
        ];
    }
}
