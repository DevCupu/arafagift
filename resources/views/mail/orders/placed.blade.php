@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;color:#082016;">Pesanan diterima</h1>
    <p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#475569;">
        Halo {{ $order->customer_name }}, terima kasih sudah memesan di {{ $store['name'] }}.
        Pesanan <strong style="color:#082016;">{{ $order->order_number }}</strong> sudah kami catat.
    </p>

    @include('mail.orders._summary')

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;border:1px solid #E4DDCC;border-radius:10px;">
        <tr>
            <td style="padding:14px 16px;">
                <p style="margin:0 0 6px;font-size:11px;text-transform:uppercase;letter-spacing:0.12em;color:#6B7A70;font-weight:700;">Pembayaran</p>
                <p style="margin:0;font-size:14px;font-weight:600;color:#082016;">{{ $order->paymentMethod?->name ?? 'Belum ditentukan' }}</p>

                @if ($order->paymentMethod?->code === 'cod')
                    <p style="margin:8px 0 0;font-size:13px;line-height:1.6;color:#475569;">
                        Bayar tunai saat pesanan diterima atau diambil di toko. Tidak ada batas waktu pembayaran.
                    </p>
                @else
                    @if ($order->payment_due_at)
                        <p style="margin:8px 0 0;font-size:13px;line-height:1.6;color:#475569;">
                            Selesaikan pembayaran sebelum
                            <strong style="color:#B45309;">{{ $order->payment_due_at->timezone('Asia/Makassar')->format('d M Y, H:i') }} WITA</strong>.
                        </p>
                    @endif

                    @if ($order->paymentMethod?->code === 'transfer' && ($store['bankName'] || $store['bankAccountNumber']))
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:10px;background:#F6F2E8;border-radius:8px;">
                            <tr>
                                <td style="padding:10px 12px;font-size:12px;line-height:1.8;color:#1F2D24;">
                                    <strong>{{ $store['bankName'] ?: 'Transfer bank' }}</strong><br>
                                    No. rekening: <strong>{{ $store['bankAccountNumber'] ?: '-' }}</strong><br>
                                    Atas nama: {{ $store['bankAccountName'] ?: '-' }}
                                </td>
                            </tr>
                        </table>
                    @elseif ($order->paymentMethod?->code === 'qris')
                        <p style="margin:8px 0 0;font-size:13px;line-height:1.6;color:#475569;">
                            Kode QRIS bisa dilihat di halaman lacak pesanan. Scan, lalu unggah bukti pembayarannya.
                        </p>
                    @endif
                @endif
            </td>
        </tr>
    </table>

    @if ($order->fulfillment_type === 'pickup')
        <p style="margin:0 0 16px;font-size:13px;line-height:1.7;color:#475569;">
            Pesanan ini akan diambil di toko. Kami akan mengabari lewat WhatsApp saat pesanan siap.
        </p>
    @else
        <p style="margin:0 0 16px;font-size:13px;line-height:1.7;color:#475569;">
            Dikirim ke: {{ $order->address }}, {{ $order->city }}{{ $order->province ? ', '.$order->province : '' }}{{ $order->postal_code ? ' '.$order->postal_code : '' }}.
        </p>
    @endif

    @if ($order->gift_message)
        <p style="margin:0 0 16px;padding:10px 12px;background:#FBF7EC;border-left:3px solid #D8B24A;font-size:13px;line-height:1.6;color:#475569;">
            Kartu ucapan: &ldquo;{{ $order->gift_message }}&rdquo;
        </p>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0 8px;">
        <tr>
            <td style="border-radius:8px;background:#082016;">
                <a href="{{ $trackingUrl }}" style="display:inline-block;padding:12px 22px;font-size:13px;font-weight:600;color:#FFFFFF;text-decoration:none;">
                    Lacak pesanan &amp; unggah bukti
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:12px;line-height:1.7;color:#6B7A70;">
        Butuh bantuan? Balas email ini atau hubungi WhatsApp {{ $store['whatsapp'] ?: '-' }}.
    </p>
@endsection
