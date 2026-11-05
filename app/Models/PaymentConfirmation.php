<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu kali pelanggan mengirim bukti pembayaran.
 *
 * Bukti lama sengaja tidak dihapus saat ada unggahan baru: kalau admin menolak
 * bukti yang salah, riwayatnya tetap ada untuk audit. Yang dipakai halaman
 * adalah bukti terbaru.
 */
#[Fillable([
    'order_id', 'path', 'original_name', 'mime', 'size', 'amount',
    'sender_name', 'note', 'status', 'reviewed_by', 'reviewed_at', 'rejection_reason',
])]
class PaymentConfirmation extends Model
{
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'size' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_VERIFIED => 'Terverifikasi',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Menunggu verifikasi',
        };
    }

    /**
     * @return array{id: int, fileName: string|null, mime: string, isImage: bool, size: int, amount: int|null, senderName: string|null, note: string|null, status: string, statusLabel: string, submittedAt: string|null, reviewedAt: string|null, reviewer: string|null, rejectionReason: string|null, url: string}
     */
    public function toCatalog(): array
    {
        return [
            'id' => $this->id,
            'fileName' => $this->original_name ?: basename((string) $this->path),
            'mime' => (string) $this->mime,
            'isImage' => $this->isImage(),
            'size' => (int) $this->size,
            'amount' => $this->amount,
            'senderName' => $this->sender_name,
            'note' => $this->note,
            'status' => $this->status,
            'statusLabel' => $this->statusLabel(),
            'submittedAt' => $this->created_at?->toIso8601String(),
            'reviewedAt' => $this->reviewed_at?->toIso8601String(),
            'reviewer' => $this->reviewer?->name,
            'rejectionReason' => $this->rejection_reason,
            // Bukti bayar sensitif: hanya bisa dibuka admin yang login.
            'url' => route('admin.payment.proof', $this),
        ];
    }
}
