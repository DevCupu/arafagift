@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;color:#082016;">Bukti pembayaran baru</h1>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#475569;">
        Pelanggan sudah mengunggah bukti pembayaran untuk pesanan
        <strong style="color:#082016;">{{ $order->order_number }}</strong>.
        Mohon verifikasi agar pesanan bisa diproses.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;border:1px solid #E4DDCC;border-radius:10px;">
        <tr>
            <td style="padding:14px 16px;font-size:12px;line-height:1.9;color:#1F2D24;">
                <strong>Pelanggan:</strong> {{ $order->customer_name }}<br>
                <strong>WhatsApp:</strong> {{ $order->customer_phone }}<br>
                <strong>Total pesanan:</strong> Rp{{ number_format((int) $order->total, 0, ',', '.') }}<br>
                @if ($extra['amount'])
                    <strong>Nominal transfer:</strong> Rp{{ number_format((int) $extra['amount'], 0, ',', '.') }}<br>
                @endif
                @if ($extra['sender'])
                    <strong>Nama pengirim:</strong> {{ $extra['sender'] }}<br>
                @endif
                @if ($extra['note'])
                    <strong>Catatan pelanggan:</strong> {{ $extra['note'] }}<br>
                @endif
                <strong>Dikirim:</strong> {{ $extra['confirmation']->created_at?->timezone('Asia/Makassar')->format('d M Y, H:i') }} WITA
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 8px;">
        <tr>
            <td style="border-radius:8px;background:#082016;">
                <a href="{{ $extra['adminUrl'] }}" style="display:inline-block;padding:12px 22px;font-size:13px;font-weight:600;color:#FFFFFF;text-decoration:none;">
                    Verifikasi di panel admin
                </a>
            </td>
            <td style="width:10px;"></td>
            <td style="border-radius:8px;border:1px solid #082016;">
                <a href="{{ $extra['proofUrl'] }}" style="display:inline-block;padding:11px 22px;font-size:13px;font-weight:600;color:#082016;text-decoration:none;">
                    Buka file bukti
                </a>
            </td>
        </tr>
    </table>
@endsection
