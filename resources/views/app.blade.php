<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <link rel="icon" type="image/png" href="/favicon.png" />
        <!-- viewport-fit=cover wajib untuk env(safe-area-inset-*) pada iPhone X+ -->
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
        <meta property="og:site_name" content="Arafagift" />
        <meta property="og:locale" content="id_ID" />
        <meta name="theme-color" content="#082016" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
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

        {{--
            Preload gambar LCP.

            Foto hero homepage adalah elemen terbesar yang dimuat pertama, dan
            <picture> baru bisa dipilih setelah CSS dan SFC selesai
            diurai. Tanpa preload, elemen itu baru ditemukan setelah Vue
            mount, jadi preload di sini memotong beberapa ratus milidetik dari
            LCP.

            Yang di-preload adalah AVIF, bukan WebP: hanya satu dari keduanya
            akan benar-benar diunduh. Browser yang tidak bisa AVIF mengabaikan
            preload ini (karena type tidak cocok) lalu langsung mengambil
            <source type="image/webp"> tanpa mengunduh dua kali.

            Hanya homepage yang punya heroImage, jadi halaman lain tidak
            ikut mem-payload apa pun.
        --}}
        @if (! empty($page['props']['heroImage']['avif']))
            <link
                rel="preload"
                as="image"
                type="image/avif"
                imagesrcset="{{ $page['props']['heroImage']['avif'] }}"
                imagesizes="100vw"
                fetchpriority="high"
            />
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js', "resources/js/pages/{$page['component']}.vue"])

        {{-- Tag SEO per halaman dikirim controller sebagai prop `seoHead` (lihat
             App\Support\Seo\PageSeo) dan dirender di sini, sebelum JS berjalan.
             Kalau meta ini hanya diletakkan di komponen <Head> Vue, crawler dan
             social scraper akan menerima halaman tanpa judul maupun deskripsi
             karena <div id="app"> baru diisi setelah hydration. --}}
        @include('partials.seo-head', ['seo' => $page['props']['seoHead'] ?? null])

        {{-- Entitas toko berlaku untuk semua halaman, jadi dicetak sekali di
             layout dan diambil dari tabel settings. PageSeo hanya menambahkan
             graf per halaman (Produk, Remah roti, FAQ) sebagai blok ld+json
             terpisah, yang tetap valid untuk Google. --}}
        @php($storeJsonLd = \App\Support\Seo\JsonLd::encode(\App\Support\Seo\StoreJsonLd::graph($page['props']['store'] ?? [])))
        @if ($storeJsonLd)
            <script type="application/ld+json">{!! $storeJsonLd !!}</script>
        @endif

        {{-- Tag ini tetap dikosongkan di server, tapi Inertia membutuhkan head
             manager-nya tetap terpasang supaya komponen <Head :title="seoHead.title" />
             bisa memperbarui <title> saat navigasi SPA tanpa full reload. Meta,
             canonical, dan OG sengaja tidak diserialisasi ke sini agar tidak
             ada tag ganda. --}}
        <x-inertia::head />
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
