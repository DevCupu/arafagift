@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;color:#082016;">Pembayaran lunas</h1>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#475569;">
        Halo {{ $order->customer_name }}, pembayaran untuk pesanan
        <strong style="color:#082016;">{{ $order->order_number }}</strong> sudah kami terima dan verifikasi.
        Pesanan akan segera diproses.
    </p>

    @include('mail.orders._summary')

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;border:1px solid #D7E5D9;border-radius:10px;background:#F3F8F3;">
        <tr>
            <td style="padding:14px 16px;font-size:13px;line-height:1.8;color:#1F2D24;">
                Status pembayaran: <strong style="color:#356B45;">Lunas</strong><br>
                @if ($order->payment_confirmed_at)
                    Dikonfirmasi: {{ $order->payment_confirmed_at->timezone('Asia/Makassar')->format('d M Y, H:i') }} WITA<br>
                @endif
                @if ($order->payment_reference)
                    Referensi: {{ $order->payment_reference }}
                @endif
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 8px;">
        <tr>
            <td style="border-radius:8px;background:#082016;">
                <a href="{{ $trackingUrl }}" style="display:inline-block;padding:12px 22px;font-size:13px;font-weight:600;color:#FFFFFF;text-decoration:none;">
                    Lihat status pesanan
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:12px;line-height:1.7;color:#6B7A70;">
        Terima kasih sudah berbelanja di {{ $store['name'] }}.
    </p>
@endsection
