# ArafahGift.id — Storefront & Admin

E-commerce untuk brand oleh-oleh & gift Umrah/Hajj **ArafahGift.id**.
Backend **Laravel 13** (MySQL) + frontend **Vue 3 (Composition API)** disambungkan lewat
**Inertia.js** — satu aplikasi, routing di sisi server, tanpa REST API terpisah.

---

## Menjalankan

Butuh PHP 8.3+, Composer, Node 18+, dan MySQL.

```bash
composer install
npm install
cp .env.example .env      # lalu sesuaikan DB_* dan jalankan php artisan key:generate
php artisan migrate --seed
composer run dev          # menjalankan server, queue listener, dan Vite bersamaan
```

Atau jalankan manual di dua terminal:

```bash
php artisan serve
npm run dev
```

Build produksi:

```bash
npm run build             # hasil di public/build/
```

---

## Peta halaman

**Storefront** (disambungkan ke database)

| Route | Halaman | Controller |
|---|---|---|
| `/` | Homepage | `HomeController` |
| `/koleksi`, `/koleksi/{category}` | Katalog + filter kategori, occasion, harga, sort | `CollectionController` |
| `/produk/{slug}` | Detail produk | `ProductController` |
| `/keranjang` | Keranjang (localStorage, drawer tersedia di semua halaman) | — |
| `/checkout` | Checkout 4 langkah (layout tanpa navigasi), `POST` menulis order ke DB | `CheckoutController` |
| `/checkout/selesai/{order}` | Konfirmasi pesanan | `OrderConfirmationController` |
| `/tentang`, `/faq` | Brand story, FAQ | `PageController` |

**Akun pelanggan** dan **Admin** — routenya sudah lengkap (lihat `routes/web.php`), tapi
halamannya **masih membaca data statis** dari `resources/js/data/{catalog,admin}.js`,
bukan database. Ini disengaja — fase migrasi berikutnya.

Belum ada autentikasi — `/admin` sengaja dibiarkan terbuka supaya mudah ditinjau.
Tambahkan route guard sebelum dipakai di produksi.

---

## Struktur

```
app/
├─ Http/Controllers/        ← HomeController, CollectionController, ProductController,
│                              CheckoutController, OrderConfirmationController, PageController
└─ Models/                  ← Product, Category, Occasion, Order, OrderItem, ShippingMethod,
                               PaymentMethod, Promotion, Setting, Content, Testimonial, Faq,
                               Address, Wishlist, User (+ toCatalog() mappers ke bentuk JS lama)
database/
├─ migrations/
└─ seeders/                 ← transkrip manual dari dummy data lama, bukan Faker
resources/
├─ css/app.css               ← design token (sama seperti dulu) + Tailwind v4 @theme
├─ views/app.blade.php       ← shell HTML + head (font, meta)
└─ js/
   ├─ app.js                 ← bootstrap createInertiaApp
   ├─ data/                  ← catalog.js, admin.js, content.js — mirror statis untuk
   │                            halaman admin/akun yang belum disambung DB
   ├─ composables/            useCart, useWishlist, useToast, useFormat, useReveal
   ├─ components/             art/, ui/, storefront/, admin/
   ├─ layouts/                StorefrontLayout, AdminLayout, BareLayout (checkout)
   └─ pages/                  shop/, account/, admin/
routes/web.php
src/                         ← kode Vite SPA lama, disimpan sebagai referensi migrasi
```

---

## Mengganti warna & font

Token warna tetap di **`resources/css/app.css`** (blok `:root`, format channel RGB —
sama seperti sebelumnya), dipetakan ke Tailwind lewat `@theme inline`. Ubah satu baris,
seluruh situs ikut berubah.

Font dimuat lewat Google Fonts di `resources/views/app.blade.php`: **Poppins** (display)
dan **Plus Jakarta Sans** (body).

---

## Menyambungkan halaman admin & akun ke database (fase berikutnya)

Skema tabelnya sudah ada dan sudah terisi (`promotions`, `settings`, `contents` untuk
konten homepage, `addresses`, `wishlists`, kolom profil di `users`). Yang perlu dikerjakan:

1. Buat controller per halaman admin (produk, kategori, pesanan, pelanggan, inventori,
   promo, konten, laporan, pengaturan) menggantikan `Route::get(..., fn () => Inertia::render(...))`
   di `routes/web.php` dengan controller yang membaca dari Eloquent.
2. Ganti import `@/data/catalog` / `@/data/admin` di halaman terkait dengan prop dari controller.
3. Tambah autentikasi (login admin, login pelanggan) + route guard/middleware.
4. Sambungkan `useWishlist.js` ke tabel `wishlists` (saat ini masih in-memory, terpisah
   dari data produk asli).

---

## Yang belum dikerjakan

- Autentikasi & route guard (login pelanggan, login admin)
- Controller database untuk halaman admin & akun (lihat bagian di atas)
- Integrasi pembayaran dan ongkir nyata (checkout sudah menulis order ke DB, tapi
  belum ada gateway pembayaran sungguhan)
- Halaman legal: kebijakan privasi, syarat & ketentuan, pengembalian
- Upload gambar di form produk dan halaman konten
- Test otomatis
