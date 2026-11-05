@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;color:#082016;">Bukti pembayaran perlu diperbaiki</h1>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#475569;">
        Halo {{ $order->customer_name }}, bukti pembayaran untuk pesanan
        <strong style="color:#082016;">{{ $order->order_number }}</strong> belum bisa kami verifikasi.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;border:1px solid #E8C9C9;border-radius:10px;background:#FBF1F1;">
        <tr>
            <td style="padding:14px 16px;font-size:13px;line-height:1.7;color:#1F2D24;">
                <strong>Alasan:</strong><br>
                {{ $extra['reason'] }}
            </td>
        </tr>
    </table>

    @if ($order->payment_due_at)
        <p style="margin:0 0 16px;font-size:13px;line-height:1.7;color:#475569;">
            Silakan unggah ulang bukti sebelum
            <strong style="color:#B45309;">{{ $order->payment_due_at->timezone('Asia/Makassar')->format('d M Y, H:i') }} WITA</strong>.
        </p>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 8px;">
        <tr>
            <td style="border-radius:8px;background:#082016;">
                <a href="{{ $trackingUrl }}" style="display:inline-block;padding:12px 22px;font-size:13px;font-weight:600;color:#FFFFFF;text-decoration:none;">
                    Unggah bukti pengganti
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:12px;line-height:1.7;color:#6B7A70;">
        Sudah merasa benar? Hubungi WhatsApp {{ $store['whatsapp'] ?: '-' }} supaya bisa kami cek ulang.
    </p>
@endsection
