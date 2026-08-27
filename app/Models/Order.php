<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_number', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
    'address', 'city', 'province', 'postal_code', 'shipping_method_id', 'shipping_cost',
    'payment_method_id', 'gift_message', 'hide_invoice', 'status', 'channel', 'note', 'awb',
    'subtotal', 'total',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'hide_invoice' => 'boolean',
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

    /**
     * @return array{id: string, customer: string, email: string, phone: string, date: string, payment: string, status: string, channel: string, note: string|null, address: string, shipping: array{method: string, cost: float|int, courier: string, awb: string|null}, items: array<int, array{name: string, sku: string, qty: int, price: float|int, art: string}>}
     */
    public function toCatalog(): array
    {
        return [
            'id' => $this->order_number,
            'customer' => $this->customer_name,
            'email' => $this->customer_email,
            'phone' => $this->customer_phone,
            'date' => $this->created_at->toIso8601String(),
            'payment' => optional($this->paymentMethod)->name ?? 'Belum ditentukan',
            'status' => $this->status,
            'channel' => $this->channel,
            'note' => $this->note,
            'address' => trim(implode(', ', array_filter([
                $this->address, $this->city, $this->province,
            ])).' '.$this->postal_code),
            'shipping' => [
                'method' => optional($this->shippingMethod)->name ?? 'Belum ditentukan',
                'cost' => $this->shipping_cost,
                'courier' => optional($this->shippingMethod)->name ?? 'Belum ditentukan',
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
