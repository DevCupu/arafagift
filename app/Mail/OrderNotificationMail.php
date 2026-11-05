<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\StoreSettingsCache;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Satu mailable untuk semua notifikasi pesanan.
 *
 * Template dipilih lewat nama view supaya isi tiap email tetap terpisah dan
 * mudah diubah tanpa membuat banyak kelas. Pengiriman dilakukan sync (tanpa
 * queue) karena hosting tidak menjalankan worker.
 */
class OrderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public Order $order,
        public string $template,
        public string $subjectLine,
        public array $extra = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        $this->order->loadMissing(['items', 'paymentMethod']);

        return new Content(
            view: 'mail.orders.'.$this->template,
            with: [
                'order' => $this->order,
                'extra' => $this->extra,
                'store' => StoreSettingsCache::store(),
                'trackingUrl' => route('order.track.token', $this->order->tracking_token),
                'adminUrl' => route('admin.order', $this->order),
                'homeUrl' => route('home'),
            ],
        );
    }
}
