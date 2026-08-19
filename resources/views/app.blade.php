<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <link rel="icon" type="image/png" href="/favicon.png" />
        <!-- viewport-fit=cover wajib untuk env(safe-area-inset-*) pada iPhone X+ -->
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
        <meta
            name="description"
            content="Koleksi oleh-oleh dan hadiah Umrah &amp; Hajj: kurma premium, sajadah, tasbih, dan gift set dengan packaging elegan. Siap untuk keluarga, sahabat, dan rombongan."
        />
        <meta name="theme-color" content="#082016" />
        <!-- Mobile web app tags -->
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="mobile-web-app-capable" content="yes" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap"
            rel="stylesheet"
        />

        @vite(['resources/css/app.css', 'resources/js/app.js', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>ArafahGift.id — Oleh-oleh Umrah &amp; Hajj yang dipilih dengan hati</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
