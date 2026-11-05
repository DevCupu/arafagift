<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentConfirmation;
use App\Services\OrderLifecycle;
use App\Services\OrderNotifier;
use App\Support\Seo\PageSeo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingController extends Controller
{
    public function index(Request $request): Response
    {
        OrderLifecycle::sweepIfDue();

        $validated = $request->validate([
            'order_number' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        if (empty($validated['order_number']) || empty($validated['phone'])) {
            return Inertia::render('shop/TrackOrderPage', [
                'seoHead' => PageSeo::noindex('Lacak Pesanan', follow: true)->toArray(),
            ]);
        }

        $order = Order::where('order_number', trim($validated['order_number']))->first();
        $digits = fn (string $value) => substr(preg_replace('/\D/', '', $value), -9);

        $found = $order && $digits($order->customer_phone) === $digits($validated['phone']);

        return Inertia::render('shop/TrackOrderPage', [
            'order' => $found ? $this->catalog($order) : null,
            'searched' => true,
            'seoHead' => PageSeo::noindex('Lacak Pesanan', follow: true)->toArray(),
        ]);
    }

    /**
     * Link tracking yang dikirim ke WhatsApp: token acak, bukan nomor pesanan,
     * supaya orang lain tidak bisa menebak pesanan pelanggan.
     */
    public function showByToken(string $token): Response
    {
        OrderLifecycle::sweepIfDue();

        $order = Order::where('tracking_token', $token)->first();

        abort_unless($order, 404);

        return Inertia::render('shop/TrackOrderPage', [
            'order' => $this->catalog($order),
            'searched' => true,
            'seoHead' => PageSeo::noindex('Lacak Pesanan', follow: true)->toArray(),
        ]);
    }

    /**
     * Pelanggan (termasuk tamu) mengunggah bukti transfer/QRIS lewat link token.
     * File disimpan di disk private dan hanya bisa dibuka admin.
     */
    public function uploadProof(Request $request, string $token): RedirectResponse
    {
        OrderLifecycle::sweepIfDue();

        $order = Order::where('tracking_token', $token)->with('paymentMethod')->first();
        abort_unless($order, 404);

        $validated = $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'amount' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'sender_name' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'proof.required' => 'Pilih file bukti pembayaran.',
            'proof.mimes' => 'Format bukti harus JPG, PNG, WebP, atau PDF.',
            'proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
            'amount.integer' => 'Nominal transfer harus berupa angka.',
        ]);

        if ($order->paymentMethod?->code === 'cod') {
            return back()->withErrors([
                'proof' => 'Pesanan COD dibayar saat barang diterima, jadi tidak perlu unggah bukti.',
            ]);
        }

        if ($order->status === 'cancelled') {
            return back()->withErrors([
                'proof' => 'Pesanan ini sudah dibatalkan, bukti pembayaran tidak bisa dikirim.',
            ]);
        }

        if (in_array($order->payment_status, ['paid', 'refunded'], true)) {
            return back()->withErrors([
                'proof' => 'Pembayaran pesanan ini sudah selesai.',
            ]);
        }

        $file = $request->file('proof');
        $path = $file->store('payment-proofs/'.$order->order_number, 'local');

        $from = $order->payment_status ?? 'unpaid';

        $confirmation = DB::transaction(function () use ($order, $file, $path, $from, $validated, $request): PaymentConfirmation {
            $confirmation = $order->paymentConfirmations()->create([
                'path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                // MIME dari file asli (fileinfo), bukan klaim Content-Type klien.
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize() ?: 0,
                'amount' => $validated['amount'] ?? null,
                'sender_name' => $validated['sender_name'] ?? null,
                'note' => $validated['note'] ?? null,
                'status' => PaymentConfirmation::STATUS_SUBMITTED,
            ]);

            $order->update(['payment_status' => 'waiting_confirmation']);

            OrderEvent::log($order, OrderEvent::TYPE_PROOF_SUBMITTED, $from, 'waiting_confirmation', $request->user(), [
                'channel' => 'customer',
            ]);

            return $confirmation;
        });

        OrderNotifier::paymentProofSubmitted($order, $confirmation);

        return back()->with('success', 'Bukti pembayaran terkirim. Kami akan memverifikasinya maksimal 1×24 jam.');
    }

    public function cancel(Request $request, string $token): RedirectResponse
    {
        $order = Order::where('tracking_token', $token)->first();
        abort_unless($order, 404);

        return OrderLifecycle::cancelByCustomer($order, $request->user())
            ? back()->with('success', 'Pesanan dibatalkan.')
            : back()->withErrors(['cancel' => 'Pesanan ini sudah diproses atau dibayar, sehingga tidak bisa dibatalkan sendiri. Hubungi kami via WhatsApp.']);
    }

    private function catalog(Order $order): array
    {
        return $order
            ->load(['items', 'paymentMethod', 'paymentConfirmations', 'latestPaymentConfirmation'])
            ->toCatalog();
    }
}
