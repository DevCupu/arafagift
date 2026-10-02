<?php

use App\Models\Content;
use App\Models\Product;
use App\Models\User;
use App\Support\Image\HeroImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * ContentController::update() memvalidasi seluruh blok home sekaligus, jadi
 * payload harus lengkap di setiap test, bukan cuma di test hero.
 *
 * $extra dipakai untuk menimpa satu blok, misalnya memasang hero.image versi
 * lama supaya test penghapusannya bisa dimulai dari kondisi yang nyata.
 */
function homePayload(string $announcement, array $extra = []): array
{
    $payload = [
        'announcement' => $announcement,
        'hero' => [
            'eyebrow' => 'Oleh-oleh Haji & Umrah',
            'headline' => 'h',
            'sub' => 's',
            'cta' => ['label' => 'l', 'to' => '/koleksi'],
            'ctaSecondary' => ['label' => 'l2', 'to' => '/koleksi/gift-set'],
        ],
        'signature' => [
            'eyebrow' => 'Pilihan enthalpy',
            'title' => 't',
            'body' => 'b',
            'productSlug' => Product::factory()->create()->slug,
            'cta' => ['label' => 'l3', 'to' => '/produk/x'],
        ],
        'bulk' => [
            'eyebrow' => 'Custom',
            'title' => 'bt',
            'sub' => 'bs',
            'points' => ['Mulai 50 pcs'],
            'cta' => ['label' => 'l4', 'href' => 'https://wa.me/628192242444'],
        ],
        'story' => [
            'eyebrow' => 'Cerita kami',
            'title' => 'st',
            'body' => ['Baris pertama.'],
            'signature' => 'Arafagift',
        ],
        'instagram' => [
            'handle' => '@x',
            'title' => 'it',
            'url' => 'https://instagram.com/x',
            'posts' => [['art' => 'giftset', 'caption' => 'c']],
        ],
        'values' => [
            ['icon' => 'Gift', 'title' => 'v', 'body' => 'vb'],
        ],
    ];

    // Disusun di akhir supaya $extra bisa menambah atau menimpa kunci di dalam
    // blok yang ada, bukan mengganti seluruh blok. Menyisipkan
    // ['hero' => ['image' => ...]] harus tetap mempertahankan headline, cta,
    // dan seterusnya, karena itulah keadaan produksi sebelum hero jadi responsif.
    return array_replace_recursive($payload, $extra);
}

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lets an admin upload a hero image for the homepage', function () {
    Content::create(['key' => 'home', 'data' => homePayload('Promo')]);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        ...homePayload('Promo baru'),
        'hero_image' => UploadedFile::fake()->image('hero.jpg'),
    ])->assertSessionHasNoErrors();

    $image = Content::where('key', 'home')->first()->data['hero']['image'];
    expect($image)->not->toBeNull();
});

it('stores the uploaded hero as a relative path so the APP_URL cannot leak into the database', function () {
    Content::create(['key' => 'home', 'data' => homePayload('Promo')]);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        ...homePayload('Promo baru'),
        'hero_image' => UploadedFile::fake()->image('hero.jpg', 1800, 900),
    ])->assertSessionHasNoErrors();

    $image = Content::where('key', 'home')->first()->data['hero']['image'];

    // URL absolut dari Storage::url() pernah menyimpan host saat itu juga
    // (http://127.0.0.1:8010/storage/...) di dalam isi database, jadi hero
    // produksi bisa menunjuk ke host dev.
    expect($image)->toStartWith('content/hero-')
        ->and($image)->toEndWith('.jpg')
        ->and($image)->not->toContain('http')
        ->and($image)->not->toStartWith('/')
        ->and(Storage::disk('public')->exists($image))->toBeTrue();
});

it('writes responsive variants for an uploaded hero so the page can serve a smaller file', function () {
    Content::create(['key' => 'home', 'data' => homePayload('Promo')]);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        ...homePayload('Promo baru'),
        'hero_image' => UploadedFile::fake()->image('hero.jpg', 1800, 900),
    ])->assertSessionHasNoErrors();

    $image = Content::where('key', 'home')->first()->data['hero']['image'];
    $stem = pathinfo($image, PATHINFO_FILENAME);

    // Tanpa ladder ini, foto 4 MB apa adanya akan diunduh setiap pengunjung
    // padahal hero adalah LCP halaman.
    foreach ([640, 1024, 1440, 1750] as $width) {
        expect(Storage::disk('public')->exists("content/{$stem}-{$width}.webp"))->toBeTrue();
    }

    // AVIF bergantung pada libgd di mesin yang menjalankan test, jadi hanya
    // diperiksa kalau encoder-nya benar-benar ada.
    if (function_exists('imageavif')) {
        expect(Storage::disk('public')->exists("content/{$stem}-640.avif"))->toBeTrue();
    }
});

it('sends srcset to the browser for an uploaded hero', function () {
    Content::create(['key' => 'home', 'data' => homePayload('Promo')]);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        ...homePayload('Promo baru'),
        'hero_image' => UploadedFile::fake()->image('hero.jpg', 1800, 900),
    ])->assertSessionHasNoErrors();

    $image = Content::where('key', 'home')->first()->data['hero']['image'];
    $stem = pathinfo($image, PATHINFO_FILENAME);

    $resolved = HeroImage::resolve($image);

    expect($resolved)->not->toBeNull()
        ->and($resolved->fallback)->toBe('/storage/'.$image)
        ->and($resolved->webp)->toContain("/storage/content/{$stem}-640.webp 640w")
        ->and($resolved->width)->toBe(1800)
        ->and($resolved->height)->toBe(900);
});

it('deletes the previous hero files when the photo is replaced', function () {
    Content::create(['key' => 'home', 'data' => homePayload('Promo')]);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        ...homePayload('Promo baru'),
        'hero_image' => UploadedFile::fake()->image('hero.jpg', 1800, 900),
    ])->assertSessionHasNoErrors();

    $first = Content::where('key', 'home')->first()->data['hero']['image'];
    $firstStem = pathinfo($first, PATHINFO_FILENAME);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        ...homePayload('Promo terbaru'),
        'hero_image' => UploadedFile::fake()->image('lain.jpg', 1600, 800),
    ])->assertSessionHasNoErrors();

    $second = Content::where('key', 'home')->first()->data['hero']['image'];

    expect($second)->not->toBe($first)
        ->and(Storage::disk('public')->exists($first))->toBeFalse()
        ->and(Storage::disk('public')->exists("content/{$firstStem}-640.webp"))->toBeFalse()
        ->and(Storage::disk('public')->exists($second))->toBeTrue();
});

it('cleans up hero files that were stored as an absolute url before responsive images existed', function () {
    // HeroImageStore harus bisa membersihkan berkas yang ditulis versi lama,
    // yang menyimpan URL absolut, bukan cuma path relatif.
    $legacyUrl = 'http://127.0.0.1:8010/storage/content/hero-lama.jpg';
    Storage::disk('public')->put('content/hero-lama.jpg', 'lama');
    Storage::disk('public')->put('content/hero-lama-640.webp', 'lama');

    Content::create([
        'key' => 'home',
        'data' => homePayload('Promo', [
            'hero' => [
                'eyebrow' => 'e',
                'headline' => 'h',
                'sub' => 's',
                'cta' => ['label' => 'l', 'to' => '/koleksi'],
                'ctaSecondary' => ['label' => 'l2', 'to' => '/koleksi/gift-set'],
                'image' => $legacyUrl,
            ],
        ]),
    ]);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        ...homePayload('Promo baru'),
        'hero_image' => UploadedFile::fake()->image('baru.jpg', 1200, 600),
    ])->assertSessionHasNoErrors();

    expect(Storage::disk('public')->exists('content/hero-lama.jpg'))->toBeFalse()
        ->and(Storage::disk('public')->exists('content/hero-lama-640.webp'))->toBeFalse();
});

it('restores the built-in hero photo and deletes the uploaded files', function () {
    Content::create(['key' => 'home', 'data' => homePayload('Promo')]);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        ...homePayload('Promo baru'),
        'hero_image' => UploadedFile::fake()->image('hero.jpg', 1800, 900),
    ])->assertSessionHasNoErrors();

    $uploaded = Content::where('key', 'home')->first()->data['hero']['image'];
    $uploadedStem = pathinfo($uploaded, PATHINFO_FILENAME);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        ...homePayload('Promo reset'),
        'hero_image_reset' => true,
    ])->assertSessionHasNoErrors();

    $hero = Content::where('key', 'home')->first()->data['hero'];

    expect($hero['image'])->toBeNull()
        ->and(Storage::disk('public')->exists($uploaded))->toBeFalse()
        ->and(Storage::disk('public')->exists("content/{$uploadedStem}-1024.webp"))->toBeFalse()
        // Setelah di-reset, homepage harus kembali memakai foto bawaan.
        ->and(HeroImage::resolve($hero['image'])->fallback)->toBe(HeroImage::fallbackPath());
});

it('keeps the uploaded hero when the reset flag is sent together with a new photo', function () {
    Content::create(['key' => 'home', 'data' => homePayload('Promo')]);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        ...homePayload('Promo baru'),
        'hero_image_reset' => true,
        'hero_image' => UploadedFile::fake()->image('baru.jpg', 1200, 600),
    ])->assertSessionHasNoErrors();

    expect(Content::where('key', 'home')->first()->data['hero']['image'])->not->toBeNull();
});
