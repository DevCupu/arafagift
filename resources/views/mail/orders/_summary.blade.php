<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;border:1px solid #E4DDCC;border-radius:10px;overflow:hidden;">
    <tr>
        <td style="padding:13px 16px;background:#F6F2E8;font-size:11px;text-transform:uppercase;letter-spacing:0.12em;color:#6B7A70;font-weight:700;">
            Rincian pesanan
        </td>
    </tr>
    <tr>
        <td style="padding:8px 16px 2px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                @foreach ($order->items as $item)
                    <tr>
                        <td style="padding:7px 0;font-size:13px;color:#1F2D24;vertical-align:top;">
                            {{ $item->name }}<br>
                            <span style="font-size:11px;color:#6B7A70;">{{ $item->qty }} &times; Rp{{ number_format((int) $item->price, 0, ',', '.') }}</span>
                        </td>
                        <td align="right" style="padding:7px 0;font-size:13px;color:#1F2D24;vertical-align:top;white-space:nowrap;">
                            Rp{{ number_format((int) $item->price * (int) $item->qty, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:4px 16px 14px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding:2px 0;font-size:12px;color:#6B7A70;">Subtotal</td>
                    <td align="right" style="padding:2px 0;font-size:12px;color:#1F2D24;">Rp{{ number_format((int) $order->subtotal, 0, ',', '.') }}</td>
                </tr>
                @if ((int) $order->discount_total > 0)
                    <tr>
                        <td style="padding:2px 0;font-size:12px;color:#6B7A70;">Diskon{{ $order->voucher_code ? ' ('.$order->voucher_code.')' : '' }}</td>
                        <td align="right" style="padding:2px 0;font-size:12px;color:#1F2D24;">&minus;Rp{{ number_format((int) $order->discount_total, 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:2px 0;font-size:12px;color:#6B7A70;">Ongkir</td>
                    <td align="right" style="padding:2px 0;font-size:12px;color:#1F2D24;">
                        {{ (int) $order->shipping_cost === 0 ? 'Gratis' : 'Rp'.number_format((int) $order->shipping_cost, 0, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:9px 0 0;border-top:1px solid #E4DDCC;font-size:13px;font-weight:700;color:#082016;">Total</td>
                    <td align="right" style="padding:9px 0 0;border-top:1px solid #E4DDCC;font-size:13px;font-weight:700;color:#082016;">Rp{{ number_format((int) $order->total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
