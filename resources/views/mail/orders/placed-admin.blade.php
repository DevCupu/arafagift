@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;color:#082016;">Pesanan baru masuk</h1>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#475569;">
        Pesanan <strong style="color:#082016;">{{ $order->order_number }}</strong> dari
        {{ $order->customer_name }} baru saja dibuat lewat {{ $order->channel }}.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;border:1px solid #E4DDCC;border-radius:10px;">
        <tr>
            <td style="padding:14px 16px;font-size:12px;line-height:1.9;color:#1F2D24;">
                <strong>Pelanggan:</strong> {{ $order->customer_name }}<br>
                <strong>WhatsApp:</strong> {{ $order->customer_phone }}<br>
                <strong>Email:</strong> {{ $order->customer_email ?: '-' }}<br>
                <strong>Pembayaran:</strong> {{ $order->paymentMethod?->name ?? '-' }}<br>
                <strong>Pemenuhan:</strong> {{ $order->fulfillment_type === 'pickup' ? 'Ambil di toko' : 'Diantar' }}<br>
                @if ($order->fulfillment_type !== 'pickup')
                    <strong>Alamat:</strong> {{ $order->address }}, {{ $order->city }}{{ $order->province ? ', '.$order->province : '' }}<br>
                @endif
                @if ($order->note)
                    <strong>Catatan:</strong> {{ $order->note }}<br>
                @endif
                <strong>Dibuat:</strong> {{ $order->created_at?->timezone('Asia/Makassar')->format('d M Y, H:i') }} WITA
            </td>
        </tr>
    </table>

    @include('mail.orders._summary')

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 8px;">
        <tr>
            <td style="border-radius:8px;background:#082016;">
                <a href="{{ $adminUrl }}" style="display:inline-block;padding:12px 22px;font-size:13px;font-weight:600;color:#FFFFFF;text-decoration:none;">
                    Buka di panel admin
                </a>
            </td>
        </tr>
    </table>
@endsection
