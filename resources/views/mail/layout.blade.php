<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $store['name'] ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#F6F2E8;font-family:'Helvetica Neue',Arial,sans-serif;color:#1F2D24;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F6F2E8;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#FFFFFF;border:1px solid #E4DDCC;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#082016;padding:22px 28px;">
                            <div style="font-size:18px;letter-spacing:0.14em;text-transform:uppercase;color:#D8B24A;font-weight:700;">
                                {{ $store['name'] ?? config('app.name') }}
                            </div>
                            <div style="font-size:12px;color:#D9D2BE;margin-top:4px;">
                                {{ $store['tagline'] ?: 'Hadiah penuh makna' }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px;background:#F6F2E8;border-top:1px solid #E4DDCC;font-size:11px;color:#6B7A70;line-height:1.7;">
                            {{ $store['address'] ?: '' }}<br>
                            WhatsApp: {{ $store['whatsapp'] ?: '-' }} &middot; Email: {{ $store['email'] ?: '-' }}<br>
                            Email ini dikirim otomatis{{ isset($order) ? ' untuk pesanan '.$order->order_number : '' }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
