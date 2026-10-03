<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pemeliharaan Sistem — Arafagift</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #FAF7EE;
            color: #1F2D24;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .container {
            max-width: 580px;
            width: 100%;
            background: #FFFFFF;
            border: 1px solid rgba(200, 168, 88, 0.25);
            border-radius: 24px;
            padding: 48px 36px;
            text-align: center;
            box-shadow: 0 20px 40px -15px rgba(19, 62, 43, 0.08);
            position: relative;
            overflow: hidden;
        }
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #133E2B, #C8A858, #133E2B);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: rgba(200, 168, 88, 0.15);
            color: #8C7030;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 24px;
        }
        .badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #C8A858;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .5; transform: scale(0.85); } }
        .icon-wrap {
            width: 72px;
            height: 72px;
            margin: 0 auto 24px;
            background: #F3EFE3;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #133E2B;
        }
        h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.85rem;
            color: #133E2B;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 12px;
        }
        .message {
            font-size: 0.95rem;
            line-height: 1.65;
            color: #556B5D;
            margin-bottom: 28px;
        }
        .time-box {
            background: #F9F7F1;
            border: 1px dashed rgba(200, 168, 88, 0.4);
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 32px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: #133E2B;
        }
        .time-box strong {
            color: #8C7030;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
        }
        .btn-wa {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #133E2B;
            color: #FAF7EE;
            padding: 12px 28px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(19, 62, 43, 0.2);
            width: 100%;
            max-width: 320px;
        }
        .btn-wa:hover {
            background: #1E573E;
            transform: translateY(-1px);
        }
        .footer-note {
            margin-top: 36px;
            font-size: 0.78rem;
            color: #8A9A90;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="badge">
            <span class="badge-dot"></span>
            <span>Pemeliharaan Berkala</span>
        </div>

        <div class="icon-wrap">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
            </svg>
        </div>

        @php
            // $settings mungkin tidak tersedia saat maintenance mode (artisan down)
            // Gunakan null-safe operator untuk menghindari error
            $maintenanceTitle   = isset($settings) ? ($settings->maintenance_title  ?: 'Sistem Sedang Ditingkatkan') : 'Sistem Sedang Ditingkatkan';
            $closedMessage      = isset($settings) ? ($settings->closed_message     ?: null) : null;
            $maintenanceEndTime = isset($settings) ? ($settings->maintenance_end_time ?: null) : null;
            $storeName          = isset($settings) ? ($settings->store_name         ?: 'Arafagift') : 'Arafagift';
            $waRaw              = isset($settings) ? ($settings->whatsapp           ?: '628192242444') : '628192242444';
            $waNumber = preg_replace('/[^0-9]/', '', (string) $waRaw);
            if (str_starts_with($waNumber, '0')) {
                $waNumber = '62' . substr($waNumber, 1);
            }
            $defaultMessage = 'Kami sedang melakukan peningkatan performa dan pemeliharaan server untuk memberikan pengalaman berbelanja oleh-oleh haji dan umrah yang lebih nyaman dan aman.';
        @endphp

        <h1>{{ $maintenanceTitle }}</h1>

        <p class="message">
            {{ $closedMessage ?: $defaultMessage }}
        </p>

        @if(!empty($maintenanceEndTime))
        <div class="time-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            <span>Estimasi Buka Kembali: <strong>{{ $maintenanceEndTime }}</strong></span>
        </div>
        @endif

        <div class="actions">
            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Halo CS ' . $storeName . ', saya ingin bertanya mengenai pemesanan selama masa pemeliharaan sistem.') }}" target="_blank" rel="noopener" class="btn-wa">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                </svg>
                Hubungi Kami via WhatsApp
            </a>
        </div>

        <p class="footer-note">
            &copy; {{ date('Y') }} {{ $storeName }}. Seluruh hak cipta dilindungi.
        </p>
    </div>
</body>
</html>
