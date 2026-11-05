<?php

namespace App\Services;

use App\Mail\OrderNotificationMail;
use App\Models\Order;
use App\Models\PaymentConfirmation;
use App\Support\StoreSettingsCache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Notifikasi email transaksional untuk pesanan.
 *
 * Semua pengiriman dibungkus try/catch: kegagalan SMTP tidak boleh membuat
 * checkout atau verifikasi pembayaran ikut gagal. Email pelanggan hanya
 * dikirim kalau alamatnya valid (checkout mengizinkan email kosong).
 */
class OrderNotifier
{
    public static function orderPlaced(Order $order, bool $notifyAdmin = true): void
    {
        self::toCustomer(
            $order,
            'placed',
            "Pesanan {$order->order_number} diterima — ".self::storeName(),
            ['trackingUrl' => route('order.track.token', $order->tracking_token)],
        );

        if ($notifyAdmin) {
            self::toAdmin(
                $order,
                'placed-admin',
                "Pesanan baru {$order->order_number}",
                ['adminUrl' => route('admin.order', $order)],
            );
        }
    }

    public static function paymentProofSubmitted(Order $order, PaymentConfirmation $confirmation): void
    {
        self::toAdmin(
            $order,
            'proof-submitted',
            "Bukti pembayaran {$order->order_number} menunggu verifikasi",
            [
                'confirmation' => $confirmation,
                'amount' => $confirmation->amount,
                'sender' => $confirmation->sender_name,
                'note' => $confirmation->note,
                'proofUrl' => route('admin.payment.proof', $confirmation),
                'adminUrl' => route('admin.order', $order),
            ],
        );
    }

    public static function paymentVerified(Order $order): void
    {
        self::toCustomer(
            $order,
            'verified',
            "Pembayaran {$order->order_number} lunas — ".self::storeName(),
        );
    }

    public static function paymentRejected(Order $order, string $reason): void
    {
        self::toCustomer(
            $order,
            'rejected',
            "Bukti pembayaran {$order->order_number} perlu diperbaiki",
            ['reason' => $reason],
        );
    }

    public static function orderExpired(Order $order): void
    {
        self::toCustomer(
            $order,
            'expired',
            "Pesanan {$order->order_number} dibatalkan",
        );
    }

    public static function orderCancelled(Order $order): void
    {
        self::toCustomer($order, 'cancelled', "Pesanan {$order->order_number} dibatalkan");
    }

    public static function paymentReminder(Order $order): void
    {
        self::toCustomer($order, 'reminder', "Segera bayar pesanan {$order->order_number} — ".self::storeName());
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private static function toCustomer(Order $order, string $template, string $subject, array $extra = []): void
    {
        $email = trim((string) $order->customer_email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        self::send($email, new OrderNotificationMail($order, $template, $subject, $extra));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private static function toAdmin(Order $order, string $template, string $subject, array $extra = []): void
    {
        $store = StoreSettingsCache::store();
        $email = trim((string) ($store['email'] ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = (string) config('mail.from.address');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        self::send($email, new OrderNotificationMail($order, $template, $subject, $extra));
    }

    private static function send(string $email, OrderNotificationMail $mail): void
    {
        try {
            Mail::to($email)->send($mail);
        } catch (Throwable $e) {
            Log::warning('Email pesanan gagal dikirim.', [
                'to' => $email,
                'order' => $mail->order->order_number,
                'template' => $mail->template,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private static function storeName(): string
    {
        return (string) (StoreSettingsCache::store()['name'] ?? config('app.name'));
    }
}
