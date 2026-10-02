<?php

use App\Models\Category;
use App\Models\Content;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Image\HeroImage;
use App\Support\Seo\PageSeo;

/*
|--------------------------------------------------------------------------
| SEO head
|--------------------------------------------------------------------------
|
| Aplikasi ini tanpa SSR, jadi <div id="app"> kosong sampai JavaScript jalan.
| Kalau title/description/canonical hanya dibuat di komponen <Head> Vue, Google
| dan WhatsApp/Instagram share scraper tidak akan pernah melihatnya. Semua
| tes di bawah membaca HTML mentah dari respons, bukan payload Inertia, karena
| itulah yang sampai ke crawler.
|
*/

beforeEach(function () {
    Setting::create([
        'store_name' => 'Arafagift',
        'address' => 'Jl. Abdullah Daeng Sirua No. 61, Panakkukang, Makassar',
        'whatsapp' => '08192242444',
        'email' => 'halo@arafagift.id',
    ]);

    // HomeController::payload() memanggil firstOrFail() pada konten home, jadi
    // tanpa baris ini setiap test homepage diam-diam menguji halaman 404.
    Content::create([
        'key' => 'home',
        'data' => [
            'hero' => ['headline' => 'Hadiah dari Tanah Suci'],
            'signature' => ['productSlug' => 'tidak-ada'],
        ],
    ]);
});

it('merender title, description, dan canonical di HTML mentah homepage', function () {
    $html = $this->get('/')->getContent();

    expect(headTagCount($html, '<title>'))->toBe(1)
        ->and(headTagCount($html, '<link rel="canonical"'))->toBe(1)
        ->and(headTagCount($html, '<meta name="description"'))->toBe(1);

    expect($html)->toContain('<link rel="canonical" href="'.route('home').'">')
        ->and($html)->toContain('name="robots" content="index, follow');
});

it('menulis brand tanpa huruf h dan menyebut kata kunci inti di judul homepage', function () {
    $html = $this->get('/')->getContent();

    expect($html)->not->toContain('ArafahGift')
        ->and($html)->toContain('Arafagift — Toko Oleh-oleh Haji &amp; Umrah');
});

it('menjaga judul tetap di dalam 60 karakter termasuk suffiks brand', function () {
    Category::factory()->create([
        'name' => 'Kurma Ajwa Premium Kurma Sukkari Medjool Tanpa Sugar',
        'slug' => 'kurma-panjang',
    ]);

    $html = $this->get('/koleksi/kurma-panjang')->getContent();

    preg_match('/<title>(.*?)<\/title>/s', $html, $match);
    $title = html_entity_decode($match[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

    expect(mb_strlen($title))->toBeLessThanOrEqual(PageSeo::TITLE_MAX)
        ->and($title)->toContain('Kurma Ajwa Premium')
        ->and($title)->toEndWith('— Arafagift');
});

it('menjaga description di dalam 155 karakter', function () {
    Product::factory()->create([
        'short' => str_repeat('Kurma Ajwa premium pilihan dari Tanah Suci. ', 10),
    ]);

    $html = $this->get('/')->getContent();

    preg_match('/<meta name="description" content="(.*?)">/s', $html, $match);
    $description = html_entity_decode($match[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

    expect(mb_strlen($description))->toBeLessThanOrEqual(PageSeo::DESCRIPTION_MAX);
});

it('tidak pernah mengirim canonical dengan query string', function () {
    $html = $this->get('/koleksi?sort=murah')->getContent();

    preg_match('/<link rel="canonical" href="(.*?)">/', $html, $match);
    expect($match[1] ?? '')->toBe(route('collection'));
});

it('membuat halaman kategori punya canonical yang sesuai', function () {
    Category::factory()->create(['name' => 'Tasbih', 'slug' => 'tasbih']);

    $html = $this->get('/koleksi/tasbih')->getContent();

    expect($html)->toContain('<link rel="canonical" href="'.route('collection', ['category' => 'tasbih']).'">');
});

it('mengalihkan slug kategori berkapital ke versi kanonik dengan 301', function () {
    Category::factory()->create(['name' => 'Kurma', 'slug' => 'kurma']);

    $this->get('/koleksi/Kurma')
        ->assertStatus(301)
        ->assertRedirect(route('collection', ['category' => 'kurma']));
});

it('memberi 404 sungguhan untuk kategori yang tidak ada', function () {
    Category::factory()->create(['slug' => 'kurma']);

    // Sebelumnya /koleksi/gift-set membalas 200 dengan katalog kosong, yang
    // dibaca Google sebagai halaman tipis dan duplicate content.
    $this->get('/koleksi/gift-set')->assertNotFound();
});

it('menandai halaman filter sebagai noindex tapi tetap memakai koleksi induk sebagai canonical', function () {
    Category::factory()->create(['slug' => 'kurma']);

    $html = $this->get('/koleksi/kurma?untuk=orang-tua')->getContent();

    expect($html)->toContain('name="robots" content="noindex, nofollow"')
        ->and($html)->toContain('<link rel="canonical" href="'.route('collection', ['category' => 'kurma']).'">');
});

it('mengganti meta produk dengan schema Produk dan tidak pernah mengirim aggregateRating', function () {
    $product = Product::factory()->create(['name' => 'Kurma Ajwa Premium 500 g']);

    $html = $this->get('/produk/'.$product->slug)->getContent();

    expect($html)->toContain('<link rel="canonical" href="'.route('product', $product->slug).'">')
        ->and($html)->toContain('"@type":"Product"')
        ->and($html)->toContain('"priceCurrency":"IDR"')
        ->and($html)->toContain('"@type":"BreadcrumbList"')
        // Kolom rating masih placeholder; mengirimnya bisa kena penalti spam
        // structured data untuk seluruh halaman produk.
        ->and($html)->not->toContain('aggregateRating');
});

it('membalas 404 untuk produk non-aktif agar tidak terindeks', function () {
    $product = Product::factory()->create(['status' => 'draft']);

    $this->get('/produk/'.$product->slug)->assertNotFound();
});

it('tetap membiarkan admin membuka pratinjau produk draft', function () {
    $product = Product::factory()->create(['status' => 'draft']);
    $admin = User::factory()->create(['is_admin' => true]);

    // Tamu biasa tetap 404, itu sudah diuji test sebelumnya. Yang diuji di
    // sini adalah sisi sebaliknya: gateway 404 tidak boleh ikut menutup
    // pratinjau admin, karena itu cara satu-satunya admin mengecek isi produk
    // sebelum menerbitkannya.
    $this->actingAs($admin)->get('/produk/'.$product->slug)->assertOk();
});

it('mengganti JSON-LD FAQ ke server', function () {
    Faq::create(['question' => 'Kurma mana yang cocok?', 'answer' => 'Ajwa untuk hadiah.', 'sort_order' => 1]);

    $html = $this->get('/faq')->getContent();

    expect($html)->toContain('"@type":"FAQPage"')
        ->and($html)->toContain('Kurma mana yang cocok?');
});

it('menyertakan entitas toko dari settings di setiap halaman', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('"@type":"Store"')
        ->and($html)->toContain('"name":"Arafagift"')
        ->and($html)->toContain('"addressCountry":"ID"');
});

it('memberi noindex tanpa canonical pada halaman utilitas', function (string $path) {
    $html = $this->get($path)->getContent();

    expect($html)->toContain('name="robots" content="noindex, ')
        ->and($html)->not->toContain('<link rel="canonical"');
})->with([
    '/checkout',
    '/lacak-pesanan',
    '/halaman-yang-tidak-ada',
]);

it('melescape nilai yang mengandung markup sebelum masuk ke head', function () {
    // Nama kategori dari panel admin adalah input tebuka. Kalau tidak di-escape,
    // karakter ini bisa menutup tag <title> dan menyuntikkan markup.
    Category::factory()->create([
        'name' => 'Kurma </title><script>alert(1)</script>',
        'slug' => 'kurma-xss',
    ]);

    $html = $this->get('/koleksi/kurma-xss')->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});

it('menutup tag script di dalam JSON-LD agar tidak bisa di-breakout', function () {
    Category::factory()->create([
        'name' => 'Kurma </script><script>alert(1)</script>',
        'slug' => 'kurma-jsonld',
    ]);

    $html = $this->get('/')->getContent();

    // Flag JSON_HEX_* mengubah "<" menjadi \u003C sehingga tidak ada yang
    // bisa menutup tag script lebih awal.
    expect($html)->not->toContain('</script><script>alert(1)');
});

it('menulis blok ld+json yang benar-benar bisa di-parse browser', function () {
    Product::factory()->create(['name' => 'Kurma Ajwa / Premium 500g']);

    $html = $this->get('/')->getContent();

    expect($html)->toMatch('/<script type="application\/ld\+json">(.*?)<\/script>/s');

    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

    // Tag pembuka dan penutup harus pas. Kalau payload Inertia sempat
    // membocorkan tag </script> ke dalam JSON, blok ini tidak akan pernah
    // terbentuk dan json_decode di bawah gagal.
    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $json) {
        expect(json_decode($json, true))->toBeArray();
    }
});

it('tidak menaruh tag penutup script di dalam payload Inertia', function () {
    $html = $this->get('/')->getContent();

    // Tag </script> milik SEO sengaja dirender di Blade, bukan dikirim lewat
    // prop seoHead. Kalau bocor ke data-page, PHP akan mengubahnya menjadi
    // <\/script> dan blok ld+json di <head> tidak akan pernah tertutup.
    $start = strpos($html, 'data-page');
    $end = strpos($html, '</head>');

    expect($start)->not->toBeFalse();

    $inBody = substr($html, (int) $end, (int) $start - (int) $end);

    expect($inBody)->not->toContain('<script type="application/ld+json"');
});

/**
 * Cerminan dari smoke test di .github/workflows/deploy.yml.
 *
 * Gate deploy memanggil curl ke domain publik lalu mem-grep beberapa string.
 * Kalau salah satu assertion di sini longgar, langkah itu bisa gagal karena
 * hal yang tidak salah (false positive) dan menahan deploy yang sebenarnya
 * sehat. Jadi setiap baris di bawah harus benar-benar dijaga di sini.
 */
it('memenuhi semua syarat smoke test deploy', function () {
    $html = $this->get('/')->assertOk()->getContent();

    // Dipakai deploy: <title>, description, dan canonical harus ada di respons
    // pertama, tanpa menjalankan JavaScript.
    expect($html)->toContain('<title>');
    expect($html)->toContain('name="description"');
    expect($html)->toContain('rel="canonical"');

    // Dipakai deploy: brand lama harus benar-benar hilang dari HTML.
    // Kalau ada satu sisa saja, gate ini akan menahan setiap deploy.
    expect($html)->not->toContain('ArafahGift');

    // Dipakai deploy: sitemap harus 200 dan benar-benar berisi <urlset.
    $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($sitemap)->toContain('<urlset');

    // robots.txt disiratkan juga oleh smoke test, jadi pastikan tetap hidup.
    $this->get('/robots.txt')->assertOk();
});

/**
 * Hitung kemunculan tag di dalam <head> saja.
 *
 * String yang sama juga muncul di dalam blok data-page Inertia (tanpa
 * escaping), jadi menghitung seluruh dokumen akan selalu menghasilkan angka
 * dobel dan assertion "tepat satu tag" jadi tidak bermakna.
 */
function headTagCount(string $html, string $needle): int
{
    $start = strpos($html, '<head>');
    $end = strpos($html, '</head>');

    expect($start)->not->toBeFalse('respons harus punya <head>');
    expect($end)->not->toBeFalse('respons harus punya </head>');

    return substr_count(substr($html, (int) $start, (int) $end - (int) $start), $needle);
}

/**
 * Hero homepage adalah LCP dan <picture> baru bisa dipilih setelah JavaScript
 * selesai mengurai SFC. Tanpa preload di HTML pertama, elemen itu baru
 * ditemukan setelah Vue mount.
 */
it('mem-preload gambar hero di HTML pertama tanpa menjalankan JavaScript', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<link rel="preload"')
        ->and($html)->toContain('as="image"')
        ->and($html)->toContain('type="image/avif"')
        ->and($html)->toContain('imagesizes="100vw"')
        ->and($html)->toContain('imagesrcset=');

    // Hanya satu preload gambar. Dua tag preload untuk gambar yang sama
    // membuat browser mengunduh satu gambar dua kali. Preload untuk JS dan
    // CSS milik Vite tidak ikut dihitung.
    expect(headTagCount($html, 'as="image"'))->toBe(1);
});

/**
 * Yang di-preload harus AVIF dengan srcset yang sama persis seperti yang
 * dikirim ke client, kalau tidak browser akan mengunduh gambar yang berbeda
 * dari yang dipinta <picture> lalu wasting satu request.
 */
it('mem-preload varian yang sama persis dengan srcset yang dikirim ke client', function () {
    $html = $this->get('/')->assertOk()->getContent();

    preg_match('/imagesrcset="([^"]*)"/', $html, $preload);
    expect($preload[1] ?? '')->not->toBe('');

    $payload = inertiaPageProps($html);

    expect($payload['heroImage']['avif'])->toBe($preload[1])
        ->and($payload['heroImage']['fallback'])->toBe('/'.HeroImage::FALLBACK)
        ->and($payload['heroImage']['webp'])->toContain('/images/assets/hero-arafahgift-v2-640.webp 640w');
});

it('tidak mengirim preload gambar di halaman selain homepage', function () {
    $html = $this->get('/koleksi')->assertOk()->getContent();

    // Hanya preload gambar yang dihitung. Preload untuk JS dan CSS tetap ada
    // di halaman mana pun karena Fassad Vite.
    expect(headTagCount($html, 'as="image"'))->toBe(0);
});

/**
 * Dulu og:image memakai PNG 1,9 MB yang sama dengan LCP, jadi setiap share
 * link WhatsApp atau Instagram mengunduh hampir 2 MB.
 */
it('membirim og:image kecil yang sudah dipotong ke rasio social preview', function () {
    $html = $this->get('/')->assertOk()->getContent();

    preg_match('/<meta property="og:image" content="([^"]*)">/', $html, $match);
    $image = $match[1] ?? '';

    expect($image)->toBe(url(HeroImage::socialPath()))
        // URL absolut wajib; scraper tidak memproses path relatif.
        ->and($image)->toStartWith('http');

    $size = filesize(public_path(HeroImage::SOCIAL));

    expect($size)->toBeInt()
        ->and($size)->toBeLessThan(500 * 1024);
});

/**
 * srcset hero harus relatif dari root domain, bukan URL absolut. Payload
 * homepage disimpan di cache, jadi URL absolut akan ikut membeku memakai host
 * yang berlaku ketika payload itu dibuat.
 */
it('menyimpan path gambar hero relatif supaya host tidak ikut ter-cache', function () {
    $payload = inertiaPageProps($this->get('/')->assertOk()->getContent());

    expect($payload['heroImage']['fallback'])->toStartWith('/images/')
        ->and($payload['heroImage']['fallback'])->not->toContain('http');
});

/**
 * Ambil prop Inertia dari blok data-page.
 *
 * Atribut data-page="app" hanya berisi id elemen, bukan payload-nya. Yang
 * berisi JSON adalah script type="application/json" tepat sesudahnya.
 */
function inertiaPageProps(string $html): array
{
    $start = strpos($html, 'type="application/json">');

    expect($start)->not->toBeFalse('respons harus punya blok data-page Inertia');

    $start += strlen('type="application/json">');
    $end = strpos($html, '</script>', (int) $start);

    expect($end)->not->toBeFalse('blok data-page Inertia harus punya penutup');

    $decoded = json_decode(substr($html, (int) $start, (int) $end - (int) $start), true);

    expect($decoded)->toBeArray();

    return $decoded['props'];
}
