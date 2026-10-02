<?php

namespace App\Support\Image;

use Illuminate\Support\Facades\Storage;

/**
 * Sumber kebenaran tunggal untuk URL gambar responsif.
 *
 * Dulu setiap gambar di markup ditulis sebagai string yang diketik ulang di
 * beberapa tempat (HomePage.vue, HomeController, seeder), dan hero homepage
 * hanya punya satu sumber PNG 1,9 MB. Class ini menerima satu referensi gambar
 * lalu mengembalikan semua yang dibutuhkan <picture>: srcset AVIF, srcset
 * WebP, dan URL fallback untuk <img>.
 *
 * Dua keputusan yang sengaja diambil di sini:
 *
 * 1. Semua nilai yang dikembalikan adalah path relatif dari root domain
 *    ("/images/assets/...", "/storage/..."), bukan URL absolut. Payload
 *    homepage disimpan di Cache::flexible, jadi URL absolut yang memakai
 *    APP_URL akan ikut membeku di dalam cache dan masih memakai host lama
 *    setelah APP_URL berubah. Absolutisasi tetap terjadi di satu tempat
 *    saja, yaitu PageSeo::image() untuk og:image.
 *
 * 2. Hanya variant yang benar-benar ada di disk yang masuk ke srcset. Kalau
 *    cache homepage masih menyimpan payload versi lama sementara file
 *    variant baru belum ter-deploy, browser tetap dapat memakai fallback
 *    alih-alih meminta file yang 404.
 */
final class ResponsiveImage
{
    /**
     * Cache dimensi per-request. getimagesize() hanya membaca header file,
     * tapi homepage dipanggil sekali per request untuk beberapa gambar dan
     * setiap pembacaan header tetap syscall yang bisa dihindari.
     *
     * @var array<string, array{int, int}|null>
     */
    private static array $dimensions = [];

    private function __construct(
        public readonly string $fallback,
        public readonly string $webp,
        public readonly string $avif,
        public readonly ?int $width,
        public readonly ?int $height,
    ) {}

    /**
     * @param  list<int>  $widths  lebar variant yang diinginkan, dalam px
     */
    public static function make(string $reference, array $widths): ?self
    {
        $fallback = self::normalize($reference);

        if ($fallback === null) {
            return null;
        }

        $source = self::dimensions($fallback);

        return new self(
            fallback: $fallback,
            webp: self::srcset($fallback, $widths, $source, 'webp'),
            avif: self::srcset($fallback, $widths, $source, 'avif'),
            width: $source[0] ?? null,
            height: $source[1] ?? null,
        );
    }

    /**
     * Bentuk yang dikirim ke Blade dan ke cache payload.
     *
     * Dipakai sebagai ganti objeknya sendiri karena dua hal: array.blade.php
     * membaca prop Inertia apa adanya sebagai objek PHP, jadi
     * $page['props']['heroImage']['avif'] akan gagal kalau isinya objek; dan
     * cache driver seperti file dan redis lebih aman menyimpan array daripada
     * objek yang harus diserialisasi.
     *
     * @return array{fallback: string, webp: string, avif: string, width: int|null, height: int|null}
     */
    public function toArray(): array
    {
        return [
            'fallback' => $this->fallback,
            'webp' => $this->webp,
            'avif' => $this->avif,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /**
     * Path relatif di dalam disk "public" untuk sebuah referensi gambar, atau
     * null kalau referensinya bukan milik disk storage.
     *
     * Dipakai HeroImageStore::remove() untuk menghapus berkas hero lama. Yang
     * penting di sini adalah menerima URL absolut versi lama:
     * HeroImageStore harus bisa membersihkan berkas yang ditulis
     * ContentController sebelum ada jalur responsif, jadi database masih
     * menyimpan "/storage/content/hero-lama.jpg".
     */
    public static function storageRelative(?string $reference): ?string
    {
        $reference = trim((string) $reference);

        if ($reference === '') {
            return null;
        }

        if (preg_match('#^(?:https?:)?//#i', $reference) === 1) {
            $reference = (string) (parse_url($reference, PHP_URL_PATH) ?? '');
        }

        $reference = ltrim($reference, '/');

        // Berkas statis di public/ bukan milik disk storage, jadi tidak boleh
        // ikut dihapus bersama lama.
        if ($reference === '' || str_starts_with($reference, 'images/')) {
            return null;
        }

        $prefix = self::storagePrefix();

        if ($prefix !== '' && str_starts_with($reference, $prefix.'/')) {
            $reference = substr($reference, strlen($prefix) + 1);
        }

        return $reference === '' ? null : $reference;
    }

    /**
     * Menyamakan berbagai bentuk referensi gambar yang bisa tersimpan di
     * database menjadi satu path relatif dari root domain.
     *
     * Bentuk yang diterima:
     * - "https://host/storage/content/hero.jpg" menjadi "/storage/content/hero.jpg".
     *   URL absolut versi lama yang ditulis Storage::disk('public')->url() di
     *   ContentController. Prefix /storage ikut dilepas supaya host dan
     *   APP_URL tidak ikut terbawa.
     * - "/images/assets/x.png" tetap. Gambar statis dilayani langsung dari
     *   public/, bukan lewat symlink storage.
     * - "content/hero.jpg" menjadi "/storage/content/hero.jpg". Ini konvensi
     *   yang sama dengan Product::imageUrl() dan Category::imageUrl(), yaitu
     *   path relatif untuk hasil upload dari panel admin.
     */
    private static function normalize(string $reference): ?string
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        if (preg_match('#^(?:https?:)?//#i', $reference) === 1) {
            $reference = (string) (parse_url($reference, PHP_URL_PATH) ?? '');
        }

        $reference = ltrim($reference, '/');

        if ($reference === '') {
            return null;
        }

        if (str_starts_with($reference, 'images/')) {
            return '/'.$reference;
        }

        $relative = self::storageRelative($reference);

        if ($relative === null) {
            return null;
        }

        $prefix = self::storagePrefix();

        // Kalau disk publik dilayani dari origin tanpa sub-path ("https://cdn.id")
        // tidak ada cara menyusun path relatif, jadi URL absolut memang satu-satunya.
        return $prefix === ''
            ? Storage::disk('public')->url($relative)
            : '/'.$prefix.'/'.$relative;
    }

    /**
     * @param  list<int>  $widths
     * @param  array{int, int}|null  $source
     */
    private static function srcset(string $fallback, array $widths, ?array $source, string $format): string
    {
        if (! self::formatSupported($format)) {
            return '';
        }

        $entries = [];

        foreach (self::sortedWidths($widths, $source) as $width) {
            $path = self::variantPath($fallback, $width, $format);
            $local = self::localPath($path);

            if ($local === null || ! is_file($local)) {
                continue;
            }

            $entries[] = $path.' '.$width.'w';
        }

        return implode(', ', $entries);
    }

    /**
     * @param  list<int>  $widths
     * @param  array{int, int}|null  $source
     * @return list<int>
     */
    private static function sortedWidths(array $widths, ?array $source): array
    {
        $widths = array_values(array_unique(array_map('intval', $widths)));
        sort($widths);

        // Jangan pernah memperbesar gambar: variant 1750 dari sumber 1024
        // hanya menambah byte tanpa menambah ketajaman.
        if ($source !== null) {
            $widths = array_values(array_filter($widths, fn (int $w): bool => $w <= $source[0]));
        }

        return $widths;
    }

    /**
     * "hero-arafahgift-v2.jpg" + 640 + "webp" menjadi
     * "/images/assets/hero-arafahgift-v2-640.webp". Nama variant hanya
     * bergantung pada nama file fallback, jadi aturan yang sama berlaku untuk
     * gambar statis maupun hasil upload admin.
     *
     * Separator dipaksa ke "/" karena dirname() mengembalikan "\" di Windows,
     * sedangkan path di sini selalu bentuk URL.
     */
    private static function variantPath(string $fallback, int $width, string $format): string
    {
        $directory = trim(str_replace('\\', '/', dirname($fallback)), '/.');

        return ($directory === '' ? '' : '/'.$directory.'/')
            .pathinfo($fallback, PATHINFO_FILENAME)
            .'-'.$width
            .'.'.$format;
    }

    /**
     * @return array{int, int}|null
     */
    private static function dimensions(string $publicPath): ?array
    {
        if (array_key_exists($publicPath, self::$dimensions)) {
            return self::$dimensions[$publicPath];
        }

        $local = self::localPath($publicPath);

        $size = $local !== null && is_file($local) ? @getimagesize($local) : false;

        return self::$dimensions[$publicPath] = $size === false
            ? null
            : [(int) $size[0], (int) $size[1]];
    }

    /**
     * Terjemahkan path yang bisa diakses publik menjadi path di filesystem.
     * Mengembalikan null untuk gambar yang tidak dilayani dari disk lokal,
     * misalnya kalau disk publik diarahkan ke S3 atau CDN.
     */
    private static function localPath(string $publicPath): ?string
    {
        if (str_starts_with($publicPath, '/images/')) {
            return public_path(substr($publicPath, 1));
        }

        $prefix = self::storagePrefix();

        if ($prefix === '' || ! str_starts_with($publicPath, '/'.$prefix.'/')) {
            return null;
        }

        return Storage::disk('public')->path(substr($publicPath, strlen($prefix) + 2));
    }

    /**
     * Sub-path tempat symlink storage disajikan dari root domain. Untuk config
     * bawaan ("http://localhost/storage") hasilnya "storage".
     */
    private static function storagePrefix(): string
    {
        $url = (string) config('filesystems.disks.public.url', '');
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');

        return trim($path, '/');
    }

    private static function formatSupported(string $format): bool
    {
        return match ($format) {
            'avif' => function_exists('imageavif'),
            'webp' => function_exists('imagewebp'),
            default => false,
        };
    }
}
