{{--
    Merender tag <head> dari payload terstruktur App\Support\Seo\PageSeo.

    Semua nilai dicetak dengan {{ }}, bukan {!! !!}: nama produk dan kategori
    berasal dari panel admin, jadi harus di-escape di titik render. Tag script
    JSON-LD juga lahir di sini, bukan di payload, supaya string "</script>"
    tidak pernah ikut masuk ke blok data-page Inertia yang tidak di-escape.
--}}
@php($seo = $seo ?? null)

@if ($seo)
    @if (! empty($seo['title']))
        <title>{{ $seo['title'] }}</title>
    @endif

    @if (! empty($seo['description']))
        <meta name="description" content="{{ $seo['description'] }}">
    @endif

    @if (! empty($seo['robots']))
        <meta name="robots" content="{{ $seo['robots'] }}">
    @endif

    @if (! empty($seo['canonical']))
        <link rel="canonical" href="{{ $seo['canonical'] }}">
    @endif

    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">

    @if (! empty($seo['canonical']))
        <meta property="og:url" content="{{ $seo['canonical'] }}">
    @endif

    @if (! empty($seo['title']))
        <meta property="og:title" content="{{ $seo['title'] }}">
    @endif

    @if (! empty($seo['description']))
        <meta property="og:description" content="{{ $seo['description'] }}">
    @endif

    @if (! empty($seo['image']))
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta name="twitter:image" content="{{ $seo['image'] }}">
    @endif

    <meta name="twitter:card" content="{{ ! empty($seo['image']) ? 'summary_large_image' : 'summary' }}">

    @foreach ($seo['jsonLd'] ?? [] as $graph)
        @php($json = \App\Support\Seo\JsonLd::encode($graph))
        @if ($json)
            <script type="application/ld+json">{!! $json !!}</script>
        @endif
    @endforeach
@endif
