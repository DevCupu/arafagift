<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use App\Support\StoreSettingsCache;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Aturan siklus hidup pesanan yang dipakai lebih dari satu tempat:
 * perubahan status pembayaran dan pembatalan otomatis karena batas bayar.
 */
class OrderLifecycle
{
    /** Status pembayaran yang dikenal sistem (disimpan sebagai string). */
    public const PAYMENT_STATUSES = ['unpaid', 'waiting_confirmation', 'paid', 'refunded'];

    /**
     * Batas waktu pembayaran untuk pesanan baru. COD tidak punya batas karena
     * dibayar saat barang diterima.
     *
     * @param  array<string, mixed>  $store
     */
    public static function paymentDueAt(string $paymentCode, array $store): ?CarbonImmutable
    {
        if ($paymentCode === 'cod') {
            return null;
        }

        $hours = max(1, (int) ($store['paymentWindowHours'] ?? 24));

        return now()->addHours($hours);
    }

    public static function changePaymentStatus(
        Order $order,
        string $status,
        ?User $actor = null,
        ?string $reference = null,
        ?string $reason = null,
    ): void {
        $from = $order->payment_status ?? 'unpaid';
        $update = ['payment_status' => $status];

        if ($status === 'paid') {
            $update['payment_confirmed_at'] = $order->payment_confirmed_at ?? now();

            if (filled($reference)) {
                $update['payment_reference'] = $reference;
            }
        } elseif ($status === 'unpaid') {
            $update['payment_confirmed_at'] = null;
        }

        $order->update($update);

        if ($from !== $status) {
            OrderEvent::log(
                $order,
                OrderEvent::TYPE_PAYMENT_STATUS_CHANGED,
                $from,
                $status,
                $actor,
                array_filter([
                    'reference' => $reference,
                    'reason' => $reason,
                ], fn ($value) => $value !== null && $value !== ''),
            );
        }
    }

    /**
     * Batalkan pesanan yang melewati batas bayar dan kembalikan stoknya bila
     * sudah sempat terpotong. Aman dipanggil berkali-kali.
     *
     * Hanya status 'unpaid' yang dibatalkan otomatis. Pesanan yang sudah
     * mengirim bukti ('waiting_confirmation') menunggu keputusan admin — bukan
     * dibatalkan diam-diam sebelum admin sempat memeriksanya.
     */
    public static function expireUnpaidOrders(): int
    {
        $expired = 0;

        Order::query()
            ->where('payment_status', 'unpaid')
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->where('status', 'pending')
            ->where(function ($query): void {
                $query->whereNull('payment_method_id')
                    ->orWhereHas('paymentMethod', fn ($method) => $method->where('code', '!=', 'cod'));
            })
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$expired): void {
                foreach ($orders as $order) {
                    $didExpire = DB::transaction(function () use ($order): int {
                        $fresh = Order::query()->whereKey($order->id)->lockForUpdate()->first();

                        if (! $fresh
                            || $fresh->status !== 'pending'
                            || $fresh->payment_due_at === null
                            || $fresh->payment_due_at->isFuture()
                            || $fresh->payment_status !== 'unpaid') {
                            return 0;
                        }

                        $fresh->update(['status' => 'cancelled']);
                        OrderStock::restore($fresh);
                        OrderEvent::log($fresh, OrderEvent::TYPE_ORDER_EXPIRED, 'pending', 'cancelled', null, [
                            'reason' => 'payment_window_elapsed',
                            'payment_status' => $fresh->payment_status,
                        ]);

                        return 1;
                    });

                    if ($didExpire === 1) {
                        // Dikirim setelah commit supaya kegagalan email tidak
                        // membatalkan transaksi pembatalan.
                        OrderNotifier::orderExpired($order->fresh());
                    }

                    $expired += $didExpire;
                }
            });

        if ($expired > 0) {
            // Angka "pesanan menunggu" di sidebar admin ikut berubah.
            Cache::forget('pending-orders-count');
        }

        return $expired;
    }

    /**
     * Pelanggan membatalkan pesanannya sendiri. Hanya boleh selama belum
     * lunas dan belum diproses admin; setelah itu pembatalan lewat admin.
     */
    public static function cancelByCustomer(Order $order, ?User $actor = null): bool
    {
        $cancelled = DB::transaction(function () use ($order, $actor): bool {
            $fresh = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (! $fresh
                || $fresh->status !== 'pending'
                || ! in_array($fresh->payment_status ?? 'unpaid', ['unpaid', 'waiting_confirmation'], true)) {
                return false;
            }

            $fresh->update(['status' => 'cancelled']);
            OrderStock::restore($fresh, $actor?->id);
            OrderEvent::log($fresh, OrderEvent::TYPE_ORDER_CANCELLED, 'pending', 'cancelled', $actor, [
                'by' => 'customer',
            ]);

            return true;
        });

        if ($cancelled) {
            Cache::forget('pending-orders-count');
            OrderNotifier::orderCancelled($order->fresh());
        }

        return $cancelled;
    }

    /**
     * Kirim pengingat sekali untuk pesanan yang batas bayarnya kurang dari
     * $withinHours jam lagi. payment_reminder_sent_at mencegah pengiriman ulang.
     */
    public static function sendPaymentReminders(int $withinHours = 3): int
    {
        $sent = 0;

        Order::query()
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->whereNull('payment_reminder_sent_at')
            ->whereNotNull('payment_due_at')
            ->whereBetween('payment_due_at', [now(), now()->addHours($withinHours)])
            ->where('customer_email', '!=', '')
            ->where(function ($query): void {
                $query->whereNull('payment_method_id')
                    ->orWhereHas('paymentMethod', fn ($method) => $method->where('code', '!=', 'cod'));
            })
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$sent): void {
                foreach ($orders as $order) {
                    // Klaim dulu secara atomik supaya dua proses tidak mengirim ganda.
                    $claimed = Order::query()
                        ->whereKey($order->id)
                        ->whereNull('payment_reminder_sent_at')
                        ->update(['payment_reminder_sent_at' => now()]);

                    if ($claimed === 1) {
                        OrderNotifier::paymentReminder($order->fresh());
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    /**
     * Mulai ulang batas bayar, dipakai saat admin menolak bukti pembayaran
     * setelah tenggat lama lewat supaya pelanggan masih bisa mengunggah ulang.
     */
    public static function extendPaymentWindow(Order $order, ?int $hours = null): void
    {
        $hours ??= max(1, (int) (StoreSettingsCache::store()['paymentWindowHours'] ?? 24));

        $order->update(['payment_due_at' => now()->addHours($hours), 'payment_reminder_sent_at' => null]);
    }

    /**
     * Fallback untuk hosting tanpa cron: minimal sekali per 5 menit, saat ada
     * halaman pesanan yang dibuka. Tanpa ini, pesanan kedaluwarsa hanya
     * dibersihkan kalau scheduler benar-benar berjalan.
     */
    public static function sweepIfDue(): int
    {
        if (! Cache::add('orders-expiry-sweep', true, 300)) {
            return 0;
        }

        try {
            self::sendPaymentReminders();

            return self::expireUnpaidOrders();
        } catch (Throwable $e) {
            Log::warning('Penyapuan pesanan kedaluwarsa gagal.', ['message' => $e->getMessage()]);

            return 0;
        }
    }
}
