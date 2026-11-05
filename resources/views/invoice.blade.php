<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #F6F2E8; color: #1F2D24; font: 14px/1.6 'Helvetica Neue', Arial, sans-serif; }
        .sheet { max-width: 780px; margin: 24px auto; padding: 40px; background: #fff; border: 1px solid #E4DDCC; }
        .head { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #082016; padding-bottom: 20px; }
        h1 { margin: 0; font-size: 28px; letter-spacing: .08em; text-transform: uppercase; color: #082016; }
        .muted { color: #6B7A70; font-size: 12px; }
        .grid { display: flex; justify-content: space-between; gap: 24px; margin: 24px 0; }
        .label { font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: #6B7A70; font-weight: 700; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: #6B7A70; border-bottom: 1px solid #E4DDCC; padding: 8px 0; }
        td { padding: 9px 0; border-bottom: 1px solid #F0EBDD; vertical-align: top; }
        .r { text-align: right; white-space: nowrap; }
        .totals { margin-left: auto; width: 280px; margin-top: 16px; }
        .totals td { border: 0; padding: 3px 0; }
        .totals .grand td { border-top: 1px solid #082016; padding-top: 8px; font-weight: 700; font-size: 16px; }
        .status { display: inline-block; padding: 2px 10px; border: 1px solid #082016; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .actions { max-width: 780px; margin: 24px auto 0; text-align: right; }
        button { padding: 10px 20px; background: #082016; color: #fff; border: 0; border-radius: 8px; font-size: 13px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .sheet { margin: 0; border: 0; padding: 0; max-width: none; }
            .actions { display: none; }
            @page { margin: 16mm; }
        }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button></div>

    <div class="sheet">
        <div class="head">
            <div>
                <h1>Invoice</h1>
                <div class="muted">{{ $order->order_number }} &middot; {{ $order->created_at->timezone('Asia/Makassar')->format('d M Y') }}</div>
            </div>
            <div style="text-align:right">
                <strong>{{ $store['name'] ?? config('app.name') }}</strong>
                <div class="muted">
                    {{ $store['address'] ?? '' }}<br>
                    WhatsApp: {{ $store['whatsapp'] ?? '-' }} &middot; {{ $store['email'] ?? '-' }}
                </div>
            </div>
        </div>

        <div class="grid">
            <div>
                <div class="label">Ditagihkan kepada</div>
                <strong>{{ $order->customer_name }}</strong><br>
                {{ $order->customer_phone }}<br>
                @if ($order->customer_email) {{ $order->customer_email }}<br> @endif
                @if (! $order->isPickup())
                    {{ trim(implode(', ', array_filter([$order->address, $order->city, $order->province])).' '.$order->postal_code) }}
                @else
                    Ambil di toko
                @endif
            </div>
            <div style="text-align:right">
                <div class="label">Pembayaran</div>
                {{ $order->paymentMethod?->name ?? 'Belum ditentukan' }}<br>
                <span class="status">
                    {{ ['paid' => 'Lunas', 'refunded' => 'Dikembalikan', 'waiting_confirmation' => 'Menunggu verifikasi'][$order->payment_status] ?? 'Belum dibayar' }}
                </span>
            </div>
        </div>

        <table>
            <thead>
                <tr><th>Produk</th><th class="r">Harga</th><th class="r">Jml</th><th class="r">Subtotal</th></tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->name }}<div class="muted">{{ $item->sku }}</div></td>
                        <td class="r">Rp{{ number_format((int) $item->price, 0, ',', '.') }}</td>
                        <td class="r">{{ $item->qty }}</td>
                        <td class="r">Rp{{ number_format((int) $item->price * (int) $item->qty, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Subtotal</td><td class="r">Rp{{ number_format((int) $order->subtotal, 0, ',', '.') }}</td></tr>
            @if ((int) $order->discount_total > 0)
                <tr><td>Diskon{{ $order->voucher_code ? ' ('.$order->voucher_code.')' : '' }}</td><td class="r">&minus;Rp{{ number_format((int) $order->discount_total, 0, ',', '.') }}</td></tr>
            @endif
            <tr><td>Ongkir</td><td class="r">{{ (int) $order->shipping_cost === 0 ? 'Gratis' : 'Rp'.number_format((int) $order->shipping_cost, 0, ',', '.') }}</td></tr>
            <tr class="grand"><td>Total</td><td class="r">Rp{{ number_format((int) $order->total, 0, ',', '.') }}</td></tr>
        </table>

        <p class="muted" style="margin-top:32px">Terima kasih telah berbelanja di {{ $store['name'] ?? config('app.name') }}.</p>
    </div>
</body>
</html>
