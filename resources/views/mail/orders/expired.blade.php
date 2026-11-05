@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;color:#082016;">Pesanan dibatalkan</h1>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#475569;">
        Halo {{ $order->customer_name }}, pesanan
        <strong style="color:#082016;">{{ $order->order_number }}</strong> dibatalkan otomatis karena
        batas waktu pembayaran sudah lewat.
    </p>

    @include('mail.orders._summary')

    <p style="margin:0 0 16px;font-size:13px;line-height:1.7;color:#475569;">
        Stok produk sudah kami kembalikan. Kalau masih ingin memesan, silakan buat pesanan baru —
        kami akan dengan senang hati membantu.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 8px;">
        <tr>
            <td style="border-radius:8px;background:#082016;">
                <a href="{{ $homeUrl }}" style="display:inline-block;padding:12px 22px;font-size:13px;font-weight:600;color:#FFFFFF;text-decoration:none;">
                    Kembali ke toko
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:12px;line-height:1.7;color:#6B7A70;">
        Ada pertanyaan? Hubungi WhatsApp {{ $store['whatsapp'] ?: '-' }}.
    </p>
@endsection
