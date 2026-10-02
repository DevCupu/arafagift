<?php

namespace App\Support\Image;

/**
 * Satu-satunya tempat nama file hero homepage disebut.
 *
 * Sebelumnya path PNG muncul di tiga file sekaligus (HomePage.vue,
 * HomeController, dan OptimizeImages), dan ketiganya bisa keluar sinkron.
 * Sekarang file sumber tinggal digenerate sekali lewat app:optimize-images,
 * sementara frontend dan server seo hanya mengenal konstanta di sini.
 *
 * File sumber hidup di resources/ bukan public/, jadi yang ikut ter-deploy ke
 * server hanya hasil turunannya: WebP dan AVIF untuk <picture> serta satu JPEG
 * untuk fallback <img> dan og:image.
 */
final class HeroImage
{
    /**
     * Sumber asli, hanya dibaca oleh app:optimize-images. Tidak pernah
     * dilayani ke browser.
     */
    public const SOURCE = 'resources/images/hero-arafahgift-v2.png';

    /**
     * Public path tanpa garis miring depan. ResponsiveImage yang menambahkannya.
     */
    public const FALLBACK = 'images/assets/hero-arafahgift-v2.jpg';

    /**
     * Dipakai untuk og:image. Tanpa ini, share link WhatsApp dan Instagram
     * mengunduh PNG 1,9 MB yang sama dengan LCP homepage.
     */
    public const SOCIAL = 'images/assets/hero-arafahgift-og.jpg';

    /**
     * Lebar untuk full-bleed hero. 640 menutup mayoritas ponsel, 1440 menutup
     * laptop umum, dan 1750 hanya kepakai di layar besar atau zoom tinggi.
     *
     * @var list<int>
     */
    public const WIDTHS = [640, 1024, 1440, 1750];

    /**
     * Ad slider memakai foto yang sama, tapi hanya selapis gradient dengan
     * opacity 0,55 dan lebarnya jauh lebih kecil daripada viewport. Dua
     * responsive image dengan lebar berbeda akan membuat browser mengunduh
     * berkas yang sama dua kali, jadi slider sengaja memakai satu lebar saja
     * agar menyentuh cache dari hero.
     *
     * @var list<int>
     */
    public const SLIDER_WIDTHS = [1024];

    /**
     * Titik fokus untuk og:image. Menyesuaikan object-position 65% center yang
     * dipakai hero, supaya yang terpotong di share preview adalah bagian yang
     * memang terlihat di layar.
     */
    public const SOCIAL_FOCAL_X = 0.65;

    public const SOCIAL_FOCAL_Y = 0.5;

    public const SOCIAL_WIDTH = 1200;

    public const SOCIAL_HEIGHT = 630;

    public static function sourcePath(): string
    {
        return base_path(self::SOURCE);
    }

    /**
     * Bentuk yang boleh dipakai di markup dan di og:image, yaitu path relatif
     * dari root domain dengan garis miring depan.
     */
    public static function fallbackPath(): string
    {
        return '/'.self::FALLBACK;
    }

    public static function socialPath(): string
    {
        return '/'.self::SOCIAL;
    }

    public static function resolve(?string $stored = null): ?ResponsiveImage
    {
        return ResponsiveImage::make(self::reference($stored), self::WIDTHS);
    }

    public static function resolveSlider(?string $stored = null): ?ResponsiveImage
    {
        return ResponsiveImage::make(self::reference($stored), self::SLIDER_WIDTHS);
    }

    /**
     * Referensi hero yang benar-benar bisa dilayani. Nilai kosong dan path yang
     * sudah tidak ada di disk dikembalikan sebagai gambar default, supaya
     * homepage tidak pernah tampil dengan elemen LCP yang 404.
     */
    private static function reference(?string $stored): string
    {
        $reference = trim((string) $stored);

        if ($reference === '' || ResponsiveImage::isMissing($reference)) {
            return self::fallbackPath();
        }

        return $reference;
    }

    /**
     * Bentuk siap kirim ke Blade, Inertia, dan cache payload. Sengaja array
     * biasa, bukan objek ResponsiveImage, supaya
     * $page['props']['heroImage']['avif'] di app.blade.php tidak harus menembus
     * objek, dan cache driver seperti file atau redis tidak perlu
     * menserialisasi objek.
     *
     * @return array{fallback: string, webp: string, avif: string, width: int|null, height: int|null}|null
     */
    public static function payload(?string $stored = null): ?array
    {
        return self::resolve($stored)?->toArray();
    }

    /**
     * @return array{fallback: string, webp: string, avif: string, width: int|null, height: int|null}|null
     */
    public static function sliderPayload(?string $stored = null): ?array
    {
        return self::resolveSlider($stored)?->toArray();
    }
}
