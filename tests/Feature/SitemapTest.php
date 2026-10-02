<?php

use App\Http\Controllers\SitemapController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Setting::create([
        'store_name' => 'Arafagift',
        'address' => 'Jl. Abdullah Daeng Sirua No. 61, Panakkukang, Makassar',
        'whatsapp' => '08192242444',
        'email' => 'halo@arafagift.id',
    ]);
});

/*
|--------------------------------------------------------------------------
| Sitemap
|--------------------------------------------------------------------------
|
| /sitemap.xml adalah satu-satunya cara Google menemukan URL baru di toko ini,
| jadi status 200 dan XML yang valid adalah syarat, bukan detail. Tes ini
| guarding dua kegagalan yang sudah terjadi di produksi: sitemap 500 (seluruh
| URL jadi tidak terdiscover sama sekali) dan URL yang tercemar query string.
|
*/

it('mengembalikan 200 dengan XML yang valid', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');
    expect(sitemapXml($response->getContent())->getName())->toBe('urlset');
});

it('menyertakan seluruh halaman statis', function () {
    $locs = sitemapLocs($this->get('/sitemap.xml')->getContent());

    expect($locs)->toContain(route('home'))
        ->and($locs)->toContain(route('collection'))
        ->and($locs)->toContain(route('about'))
        ->and($locs)->toContain(route('faq'));
});

it('menyertakan kategori dan hanya produk aktif', function () {
    $category = Category::factory()->create(['slug' => 'kurma']);
    $active = Product::factory()->for($category)->create(['slug' => 'kurma-ajwa-medina']);
    $draft = Product::factory()->for($category)->create(['slug' => 'kurma-draf', 'status' => 'draft']);

    $locs = sitemapLocs($this->get('/sitemap.xml')->getContent());

    expect($locs)->toContain(route('collection', ['category' => $category->slug]))
        ->and($locs)->toContain(route('product', $active->slug))
        ->and($locs)->not->toContain(route('product', $draft->slug));
});

it('tidak pernah membocorkan query string ke dalam loc', function () {
    Category::factory()->create();
    Product::factory()->create();

    foreach (sitemapLocs($this->get('/sitemap.xml')->getContent()) as $loc) {
        expect($loc)->not->toContain('?');
    }
});

it('memberi lastmod pada produk serta changefreq dan priority pada setiap url', function () {
    Product::factory()->create();

    $content = $this->get('/sitemap.xml')->getContent();

    expect($content)->toContain('<lastmod>')
        ->and($content)->toContain('<changefreq>')
        ->and($content)->toContain('<priority>');
});

it('tidak 500 saat katalog masih kosong', function () {
    $this->get('/sitemap.xml')->assertOk();
});

it('merender XML dari entri berformat lama tanpa error dan tanpa tag kosong', function () {
    // Ini lapisan yang benar-benar rusak di produksi. Entri cache versi lama
    // hanya punya kunci loc, sedangkan versi baru juga memakai changefreq dan
    // priority. Controller sekarang memakai buildXml() murni PHP (tidak ada
    // Blade), jadi kita uji buildXml() langsung via Reflection agar tidak
    // terikat pada URL test-server (localhost) yang dicampur Cache::flexible.
    $buildXml = new ReflectionMethod(SitemapController::class, 'buildXml');
    $buildXml->setAccessible(true);

    $xml = $buildXml->invoke(null, [
        ['loc' => 'https://arafagift.id/'],
        ['loc' => 'https://arafagift.id/koleksi/kurma'],
    ]);

    expect($xml)->toContain('<loc>https://arafagift.id/</loc>')
        ->and($xml)->toContain('<loc>https://arafagift.id/koleksi/kurma</loc>')
        // Tag kosong akan ditandai Google sebagai error di Search Console.
        ->and($xml)->not->toContain('<changefreq></changefreq>')
        ->and($xml)->not->toContain('<priority></priority>')
        ->and($xml)->not->toContain('<loc></loc>')
        ->and($xml)->not->toContain('<lastmod></lastmod>');
});

it('menyertakan changefreq dan priority pada entri yang lengkap', function () {
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('<changefreq>daily</changefreq>')
        ->and($xml)->toContain('<priority>1.0</priority>');
});

it('menyimpan sitemap di key berversi agar entri basi tidak terpakai', function () {
    // Saat bentuk entri berubah, nama key harus ikut berubah. Kalau tidak,
    // cache lama masih terbaca selama 24-48 jam setelah deploy dan sitemap
    // dirender dari data yang tidak cocok dengan view sekarang.
    SitemapController::urls();

    $key = (new ReflectionClass(SitemapController::class))->getConstant('CACHE_KEY');

    expect($key)->toBe('sitemap-urls-v2')
        ->and(Cache::get($key))->toBeArray();
});

it('membuang cache sitemap lewat flush()', function () {
    // Kalau nama key ditulis ulang di beberapa pemanggil dan satu lupa
    // diperbarui, produk yang sudah dihapus tetap muncul di sitemap.
    SitemapController::urls();

    SitemapController::flush();

    $key = (new ReflectionClass(SitemapController::class))->getConstant('CACHE_KEY');

    expect(Cache::get($key))->toBeNull();
});

it('tidak 500 dan jatuh ke URL statis saja saat katalog tidak bisa dibaca', function () {
    // Guard untuk failur yang terjadi di produksi: satu masalah pada query
    // katalog tidak boleh membuat seluruh sitemap hilang, karena itu berarti
    // Google kehilangan peta URL toko ini sepenuhnya.
    Schema::drop('products');

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect(sitemapLocs($response->getContent()))->toContain(route('home'));
});

it('menyayani robots.txt yang menunjuk ke sitemap yang benar', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk();
    expect($response->getContent())
        ->toContain('Sitemap: '.route('sitemap'))
        ->toContain('Disallow: /admin');
});

function sitemapXml(string $content): SimpleXMLElement
{
    $previous = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($content);
    libxml_use_internal_errors($previous);

    expect($xml)->not->toBeFalse('sitemap harus berupa XML yang valid');

    return $xml;
}

/**
 * @return list<string>
 */
function sitemapLocs(string $content): array
{
    $xml = sitemapXml($content);
    $locs = [];

    foreach ($xml->url as $url) {
        $locs[] = (string) $url->loc;
    }

    return $locs;
}
