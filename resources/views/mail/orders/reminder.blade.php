@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;color:#082016;">Jangan lupa bayar pesanan Anda</h1>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#475569;">
        Halo {{ $order->customer_name }}, pesanan
        <strong style="color:#082016;">{{ $order->order_number }}</strong> belum dibayar. Batas pembayaran:
        <strong style="color:#B45309;">{{ $order->payment_due_at->timezone('Asia/Makassar')->format('d M Y, H:i') }} WITA</strong>.
        Setelah itu pesanan dibatalkan otomatis.
    </p>

    @include('mail.orders._summary')

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 8px;">
        <tr>
            <td style="border-radius:8px;background:#082016;">
                <a href="{{ $trackingUrl }}" style="display:inline-block;padding:12px 22px;font-size:13px;font-weight:600;color:#FFFFFF;text-decoration:none;">
                    Bayar &amp; unggah bukti
                </a>
            </td>
        </tr>
    </table>
@endsection
