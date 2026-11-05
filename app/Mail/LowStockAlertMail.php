<?php

namespace App\Mail;

use App\Models\Product;
use App\Support\StoreSettingsCache;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LowStockAlertMail extends Mailable
{
    use Queueable;

    /** @param  Collection<int, Product>  $products */
    public function __construct(public Collection $products) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Stok menipis: {$this->products->count()} produk perlu restock");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.low-stock', with: [
            'products' => $this->products,
            'store' => StoreSettingsCache::store(),
            'inventoryUrl' => route('admin.inventory'),
        ]);
    }
}
