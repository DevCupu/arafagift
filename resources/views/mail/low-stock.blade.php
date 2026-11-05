@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;color:#082016;">Stok menipis</h1>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#475569;">
        {{ $products->count() }} produk berada di bawah ambang stok minimum:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;border:1px solid #E4DDCC;border-radius:10px;">
        @foreach ($products as $product)
            <tr>
                <td style="padding:9px 16px;font-size:13px;color:#1F2D24;">{{ $product->name }} <span style="color:#6B7A70;">({{ $product->sku }})</span></td>
                <td align="right" style="padding:9px 16px;font-size:13px;font-weight:700;color:#B45309;white-space:nowrap;">
                    {{ $product->stock }} / min {{ $product->low_stock_threshold }}
                </td>
            </tr>
        @endforeach
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 8px;">
        <tr>
            <td style="border-radius:8px;background:#082016;">
                <a href="{{ $inventoryUrl }}" style="display:inline-block;padding:12px 22px;font-size:13px;font-weight:600;color:#FFFFFF;text-decoration:none;">
                    Buka inventori
                </a>
            </td>
        </tr>
    </table>
@endsection
