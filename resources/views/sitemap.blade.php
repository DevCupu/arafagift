{!! '<' . '?xml version="1.0" encoding="UTF-8"?>' !!}
{{-- Entri di sini datang dari cache, dan cache bisa masih memuat bentuk lama
     yang tidak punya kunci changefreq atau priority. Karena itu setiap kunci
     dibaca dengan operator ?? dan tidak pernah memakai $url['kunci'] langsung.
     Akses kunci yang hilang di sini menjadi ErrorException, karena Laravel
     mengubah warning PHP jadi exception, dan itu sempat membuat /sitemap.xml
     membalas 500 di produksi. --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] ?? '' }}</loc>
        @if (! empty($url['lastmod']))
        <lastmod>{{ $url['lastmod'] }}</lastmod>
        @endif
        @if (! empty($url['changefreq']))
        <changefreq>{{ $url['changefreq'] }}</changefreq>
        @endif
        @if (! empty($url['priority']))
        <priority>{{ $url['priority'] }}</priority>
        @endif
    </url>
@endforeach
</urlset>
