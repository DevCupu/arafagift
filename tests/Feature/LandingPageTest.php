<?php

use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

function heroBlock(array $overrides = []): array
{
    return [
        'type' => 'hero',
        'enabled' => true,
        'content' => array_merge([
            'eyebrow' => 'Untuk Agen Travel',
            'headline' => "Souvenir rombongan\nyang rapi",
            'sub' => 'Satu paket untuk seluruh jemaah.',
            'image' => '',
            'cta' => ['label' => 'Konsultasi', 'href' => ''],
        ], $overrides),
    ];
}

function landingPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'ArafahGift untuk Agen Travel',
        'slug' => 'arafagifttravel',
        'status' => 'publish',
        'blocks' => [heroBlock()],
    ], $overrides);
}

// ── Rendering publik ──

it('renders a published landing page with only its enabled blocks', function () {
    Page::create(landingPayload(['blocks' => [
        heroBlock(),
        [
            'type' => 'cta',
            'enabled' => false,
            'content' => ['headline' => 'Blok mati', 'sub' => '', 'note' => '', 'cta' => ['label' => 'X', 'href' => '']],
        ],
    ]]));

    $this->get('/arafagifttravel')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('shop/LandingPage')
            ->has('blocks', 1)
            ->where('blocks.0.type', 'hero')
        );
});

it('hides a draft landing page from guests but shows it to an admin', function () {
    Page::create(landingPayload(['status' => 'draft']));

    $this->get('/arafagifttravel')->assertNotFound();
    $this->actingAs($this->admin)->get('/arafagifttravel')->assertOk();
});

it('sends a noindex header so ad pages never get indexed without JS', function () {
    Page::create(landingPayload());

    $this->get('/arafagifttravel')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    // Halaman toko biasa tidak boleh ikut kena.
    expect($this->get('/tentang')->headers->get('X-Robots-Tag'))->toBeNull();
});

it('returns 404 for an unknown slug', function () {
    $this->get('/tidak-ada-halaman-ini')->assertNotFound();
});

// ── Hidrasi produk ──

it('hydrates products for produk_unggulan blocks in a single query', function () {
    $products = Product::factory()->count(4)->create(['status' => 'active']);
    $ids = $products->pluck('id')->all();

    $productBlock = fn (array $pick) => [
        'type' => 'produk_unggulan',
        'enabled' => true,
        'content' => ['eyebrow' => '', 'title' => 'Pilihan', 'productIds' => $pick],
    ];

    Page::create(landingPayload(['blocks' => [$productBlock(array_slice($ids, 0, 2))]]));

    // Panaskan cache prop bersama (home-content, settings-store) dulu, supaya
    // yang dihitung benar-benar query hidrasi produk.
    $this->get('/arafagifttravel')->assertOk();

    DB::enableQueryLog();
    $this->get('/arafagifttravel')->assertOk()
        ->assertInertia(fn ($page) => $page->has('products', 2));
    $oneBlock = count(DB::getQueryLog());

    // Blok produk kedua tidak boleh menambah query: hidrasinya di-batch.
    Page::where('slug', 'arafagifttravel')->update([
        'blocks' => json_encode([$productBlock(array_slice($ids, 0, 2)), $productBlock(array_slice($ids, 2, 2))]),
    ]);

    DB::flushQueryLog();
    $this->get('/arafagifttravel')->assertOk()
        ->assertInertia(fn ($page) => $page->has('products', 4));
    $twoBlocks = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($twoBlocks)->toBe($oneBlock);
});

it('does not leak internal product fields to the public page', function () {
    $product = Product::factory()->create(['status' => 'active', 'cost' => 50000]);

    Page::create(landingPayload(['blocks' => [[
        'type' => 'produk_unggulan',
        'enabled' => true,
        'content' => ['eyebrow' => '', 'title' => 'Pilihan', 'productIds' => [$product->id]],
    ]]]));

    $this->get('/arafagifttravel')->assertOk()->assertInertia(fn ($page) => $page
        ->has("products.{$product->id}", fn ($card) => $card
            ->where('name', $product->name)
            ->missing('cost')
            ->missing('supplier')
            ->missing('storageLocation')
            ->etc()
        )
    );
});

// ── Admin ──

it('blocks non-admins from every landing admin route', function () {
    $page = Page::create(landingPayload());
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)->get('/admin/landing')->assertForbidden();
    $this->actingAs($customer)->get('/admin/landing/baru')->assertForbidden();
    $this->actingAs($customer)->post('/admin/landing', landingPayload())->assertForbidden();
    $this->actingAs($customer)->put("/admin/landing/{$page->slug}", landingPayload())->assertForbidden();
    $this->actingAs($customer)->delete("/admin/landing/{$page->slug}")->assertForbidden();
});

it('renders the builder for a new page and for an existing one', function () {
    Page::create(landingPayload());

    $this->actingAs($this->admin)->get('/admin/landing')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('admin/LandingsPage')->has('pages', 1)
            ->where('pages.0.blockCount', 1));

    $this->actingAs($this->admin)->get('/admin/landing/baru')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('admin/LandingFormPage')->where('page', null)->has('products'));

    $this->actingAs($this->admin)->get('/admin/landing/arafagifttravel')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('admin/LandingFormPage')
            ->where('page.slug', 'arafagifttravel')->has('page.blocks', 1));
});

it('creates a landing page from the admin builder', function () {
    $this->actingAs($this->admin)
        ->post('/admin/landing', landingPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin/landing/arafagifttravel');

    expect(Page::where('slug', 'arafagifttravel')->first()->blocks)->toHaveCount(1);
});

it('persists block order exactly as submitted', function () {
    $page = Page::create(landingPayload());

    $cta = [
        'type' => 'cta',
        'enabled' => true,
        'content' => ['headline' => 'Pesan sekarang', 'sub' => '', 'note' => '', 'cta' => ['label' => 'Chat', 'href' => '']],
    ];

    $this->actingAs($this->admin)
        ->put("/admin/landing/{$page->slug}", landingPayload(['blocks' => [$cta, heroBlock()]]))
        ->assertSessionHasNoErrors();

    expect(array_column($page->fresh()->blocks, 'type'))->toBe(['cta', 'hero']);
});

it('rejects slugs that would hijack an existing route', function (string $slug) {
    $this->actingAs($this->admin)
        ->post('/admin/landing', landingPayload(['slug' => $slug]))
        ->assertSessionHasErrors('slug');

    expect(Page::where('slug', $slug)->exists())->toBeFalse();
})->with(['admin', 'tentang', 'faq', 'login', 'produk', 'koleksi']);

it('rejects unknown block types and malformed block content', function () {
    $this->actingAs($this->admin)->post('/admin/landing', landingPayload(['blocks' => [
        ['type' => 'iframe_jahat', 'enabled' => true, 'content' => []],
    ]]))->assertSessionHasErrors('blocks.0.type');

    // hero.headline wajib diisi.
    $this->actingAs($this->admin)->post('/admin/landing', landingPayload([
        'blocks' => [heroBlock(['headline' => ''])],
    ]))->assertSessionHasErrors('blocks.0.content.headline');
});

it('strips content keys that are not declared for the block type', function () {
    $this->actingAs($this->admin)->post('/admin/landing', landingPayload([
        'blocks' => [heroBlock(['onclick' => 'alert(1)', 'script' => '<script>x</script>'])],
    ]))->assertSessionHasNoErrors();

    $content = Page::where('slug', 'arafagifttravel')->first()->blocks[0]['content'];

    expect($content)->not->toHaveKey('onclick')
        ->and($content)->not->toHaveKey('script')
        ->and($content['headline'])->toBe("Souvenir rombongan\nyang rapi");
});

it('renders a banner block and rejects a slide without an image', function () {
    $this->actingAs($this->admin)->post('/admin/landing', landingPayload(['blocks' => [
        [
            'type' => 'banner',
            'enabled' => true,
            'content' => ['items' => [
                ['image' => 'https://cdn.test/promo1.jpg', 'href' => '', 'alt' => 'Promo 1', 'badge' => 'PROMO', 'title' => 'Diskon rombongan', 'ctaLabel' => 'Pesan Sekarang'],
                ['image' => 'https://cdn.test/promo2.jpg', 'href' => 'https://wa.me/x', 'alt' => 'Promo 2', 'badge' => '', 'title' => '', 'ctaLabel' => ''],
            ]],
        ],
    ]]))->assertSessionHasNoErrors();

    $this->get('/arafagifttravel')->assertOk()->assertInertia(fn ($page) => $page
        ->where('blocks.0.type', 'banner')
        ->has('blocks.0.content.items', 2)
        ->where('blocks.0.content.items.0.badge', 'PROMO')
        ->where('blocks.0.content.items.0.ctaLabel', 'Pesan Sekarang')
    );

    $this->actingAs($this->admin)->put('/admin/landing/arafagifttravel', landingPayload(['blocks' => [
        ['type' => 'banner', 'enabled' => true, 'content' => ['items' => [['image' => '', 'href' => '', 'alt' => '']]]],
    ]]))->assertSessionHasErrors('blocks.0.content.items.0.image');
});

it('supports manual products alongside catalog products in produk_unggulan', function () {
    $product = Product::factory()->create(['status' => 'active']);

    $this->actingAs($this->admin)->post('/admin/landing', landingPayload(['blocks' => [[
        'type' => 'produk_unggulan',
        'enabled' => true,
        'content' => ['eyebrow' => '', 'title' => 'Pilihan', 'productIds' => [$product->id], 'customItems' => [
            ['name' => 'Paket Custom Rombongan', 'price' => 350000, 'image' => 'https://cdn.test/custom.jpg', 'badge' => 'Baru'],
        ]],
    ]]]))->assertSessionHasNoErrors();

    $this->get('/arafagifttravel')->assertOk()->assertInertia(fn ($page) => $page
        ->has('products', 1)
        ->where('blocks.0.content.customItems.0.name', 'Paket Custom Rombongan')
        ->where('blocks.0.content.customItems.0.price', 350000)
    );
});

it('requires a name for manual products and caps combined total at 8', function () {
    $this->actingAs($this->admin)->post('/admin/landing', landingPayload(['blocks' => [[
        'type' => 'produk_unggulan',
        'enabled' => true,
        'content' => ['eyebrow' => '', 'title' => 'Pilihan', 'productIds' => [], 'customItems' => [
            ['name' => '', 'price' => 100000, 'image' => '', 'badge' => ''],
        ]],
    ]]]))->assertSessionHasErrors('blocks.0.content.customItems.0.name');

    $products = Product::factory()->count(5)->create(['status' => 'active']);
    $manual = array_fill(0, 5, ['name' => 'Manual', 'price' => 100000, 'image' => '', 'badge' => '']);

    $this->actingAs($this->admin)->post('/admin/landing', landingPayload(['blocks' => [[
        'type' => 'produk_unggulan',
        'enabled' => true,
        'content' => ['eyebrow' => '', 'title' => 'Pilihan', 'productIds' => $products->pluck('id')->all(), 'customItems' => $manual],
    ]]]))->assertSessionHasErrors('blocks.0.content.productIds');
});

it('accepts optional photo/avatar fields on nilai and testimoni items', function () {
    $this->actingAs($this->admin)->post('/admin/landing', landingPayload(['blocks' => [
        [
            'type' => 'nilai',
            'enabled' => true,
            'content' => ['title' => 'Kenapa kami', 'items' => [
                ['icon' => 'Sparkles', 'image' => 'https://cdn.test/hook.jpg', 'title' => 'Kurasi', 'body' => 'Dipilih manual.'],
            ]],
        ],
        [
            'type' => 'testimoni',
            'enabled' => true,
            'content' => ['title' => 'Kata mereka', 'items' => [
                ['name' => 'H. Zainal', 'role' => 'Owner Travel', 'quote' => 'Mantap.', 'avatar' => 'https://cdn.test/avatar.jpg'],
            ]],
        ],
    ]]))->assertSessionHasNoErrors();

    $blocks = Page::where('slug', 'arafagifttravel')->first()->blocks;

    expect($blocks[0]['content']['items'][0]['image'])->toBe('https://cdn.test/hook.jpg')
        ->and($blocks[1]['content']['items'][0]['avatar'])->toBe('https://cdn.test/avatar.jpg');
});

it('caps the number of hooks in a nilai block at five', function () {
    $hook = ['icon' => 'Sparkles', 'title' => 'Kurasi', 'body' => 'Dipilih manual.'];

    $this->actingAs($this->admin)->post('/admin/landing', landingPayload(['blocks' => [[
        'type' => 'nilai',
        'enabled' => true,
        'content' => ['title' => 'Kenapa kami', 'items' => array_fill(0, 6, $hook)],
    ]]]))->assertSessionHasErrors('blocks.0.content.items');
});

it('renders a popup block and requires a title', function () {
    $this->actingAs($this->admin)->post('/admin/landing', landingPayload(['blocks' => [
        [
            'type' => 'popup',
            'enabled' => true,
            'content' => ['image' => '', 'badge' => 'PROMO', 'title' => 'Diskon 20%', 'sub' => 'Khusus hari ini', 'ctaLabel' => 'Chat Sekarang', 'href' => '', 'delaySeconds' => '4'],
        ],
    ]]))->assertSessionHasNoErrors();

    $this->get('/arafagifttravel')->assertOk()->assertInertia(fn ($page) => $page
        ->where('blocks.0.type', 'popup')
        ->where('blocks.0.content.title', 'Diskon 20%')
        ->where('blocks.0.content.delaySeconds', '4')
    );

    $this->actingAs($this->admin)->put('/admin/landing/arafagifttravel', landingPayload(['blocks' => [
        ['type' => 'popup', 'enabled' => true, 'content' => ['title' => '']],
    ]]))->assertSessionHasErrors('blocks.0.content.title');
});

// ── Regresi routing ──

it('does not let the landing catch-all shadow existing routes', function (string $uri, string $expected) {
    $route = app('router')->getRoutes()->match(Request::create($uri));

    expect($route->getName())->toBe($expected);
})->with([
    ['/', 'home'],
    ['/tentang', 'about'],
    ['/faq', 'faq'],
    ['/koleksi', 'collection'],
    ['/login', 'login'],
    ['/produk/kurma-ajwa', 'product'],
    ['/admin/landing', 'admin.landings'],
    ['/lacak-pesanan', 'order.track'],
    // Yang memang milik landing:
    ['/arafagifttravel', 'landing'],
    ['/arafagiftexclusive1', 'landing'],
]);

// ── Halaman 404 storefront ──

it('serves the storefront 404 page for unknown product slugs', function () {
    $this->get('/produk/kurma-tidak-ada')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('shop/NotFoundPage'));
});

it('serves the storefront 404 page for missing landing slugs and the raw fallback', function (string $uri) {
    $this->get($uri)
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('shop/NotFoundPage'));
})->with(['/slug-landing-entah-apa', '/keranjang', '/dua/segmen-path']);
