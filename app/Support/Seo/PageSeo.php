<?php

namespace App\Support\Seo;

/**
 * Membangun tag <head> untuk satu rute, lalu dikembalikan sebagai list string
 * HTML mentah lewat prop Inertia `seoHead`. resources/views/app.blade.php yang
 * mencetaknya, jadi crawler dan social scraper (yang tidak pernah menjalankan
 * JS) tetap melihat meta yang benar sejak respons pertama.
 *
 * Semua nilai dinamis di-escape di sini, bukan di Blade: Blade mencetak dengan
 * {!! !!} karena isinya sudah berupa HTML jadi. Kalau escaping dilewati, nama
 * produk berisi markup dari panel admin bisa bocor ke dalam <head>.
 */
final class PageSeo
{
    /**
     * Batas yang dipakai Google: judul terpotong sekitar 60 karakter,
     * deskripsi sekitar 155. Diterapkan di sini supaya tidak ada halaman yang
     * diam-diam mengirim judul 180 karakter hanya karena satu produk punya
     * nama panjang.
     */
    public const TITLE_MAX = 60;

    public const DESCRIPTION_MAX = 155;

    public const BRAND = 'Arafagift';

    private const INDEXABLE = 'index, follow, max-image-preview:large, max-snippet:-1';

    /**
     * @param  list<array<string, mixed>>  $jsonLd
     */
    private function __construct(
        private readonly ?string $headTitle,
        private readonly string $headDescription,
        private readonly ?string $canonical,
        private readonly ?string $robots,
        private readonly string $type,
        private readonly ?string $image,
        private readonly array $jsonLd,
        private readonly bool $brandSuffix,
    ) {}

    public static function make(string $title, string $description): self
    {
        return new self(
            headTitle: $title,
            headDescription: $description,
            // Tanpa query string: /koleksi?sort=murah tidak boleh
            // men-self-canonicalize dengan parameternya sendiri.
            canonical: request()->url(),
            robots: self::INDEXABLE,
            type: 'website',
            image: null,
            jsonLd: [],
            brandSuffix: true,
        );
    }

    /**
     * Default untuk setiap halaman yang tidak menyediakan head-nya sendiri:
     * akun, admin, checkout, dan 404. Halaman publik yang lupa opt-in akan
     * berakhir di sini, yang jauh lebih baik daripada diam-diam mengirim title
     * homepage ke semua URL.
     */
    public static function noindex(?string $title = null, bool $follow = false): self
    {
        return new self(
            // Judul default sudah berupa brand, jadi suffiks tidak ditambahkan
            // lagi supaya tidak menjadi "Arafagift — Arafagift".
            headTitle: $title ?? self::BRAND,
            headDescription: '',
            // Canonical dikosongkan dengan sengaja: noindex dan canonical
            // saling bertentangan, dan halaman ini memang tidak untuk diindeks.
            canonical: null,
            robots: 'noindex, '.($follow ? 'follow' : 'nofollow'),
            type: 'website',
            image: null,
            jsonLd: [],
            brandSuffix: false,
        );
    }

    public function canonical(string $url): self
    {
        return $this->copy(canonical: $url);
    }

    public function type(string $type): self
    {
        return $this->copy(type: $type);
    }

    /**
     * Menerima URL absolut maupun path yang diawali garis miring
     * (hasil Storage::url()) dan menjadikannya absolut, karena social scraper
     * hanya memproses og:image yang absolut.
     */
    public function image(?string $url): self
    {
        if ($url !== null && str_starts_with($url, '/')) {
            $url = url($url);
        }

        return $this->copy(image: $url);
    }

    /**
     * Untuk halaman yang sudah menulis brand-nya sendiri di depan, misalnya
     * homepage. Tanpa ini judulnya jadi "Arafagift — ... — Arafagift".
     */
    public function withoutBrandSuffix(): self
    {
        return $this->copy(brandSuffix: false);
    }

    /**
     * Menahan halaman ini dari indeks tanpa membuang judul dan deskripsinya.
     * Dipakai untuk kombinasi filter yang usefulness-nya nyata tapi URL-nya
     * tidak kanonik, misalnya /koleksi/kurma?untuk=hadiah-hajj.
     */
    public function withNoIndex(bool $follow = false): self
    {
        return $this->copy(robots: 'noindex, '.($follow ? 'follow' : 'nofollow'));
    }

    /**
     * @param  array<string, mixed>  $graph
     */
    public function jsonLd(array $graph): self
    {
        return $this->copy(jsonLd: [$graph]);
    }

    /**
     * Bentuk terstruktur, bukan string HTML.
     *
     * Sengaja tidak mengembalikan tag siap cetak: prop Inertia diserialisasi ke
     * <script type="application/json"> tanpa escaping sama sekali
     * (vendor/inertiajs/inertia-laravel/src/View/Components/App.php), jadi tag
     * </script> milik kita akan menutup blok itu lebih awal dan merusak
     * halaman. Rendering dipindah ke resources/views/partials/seo-head.blade.php
     * supaya setiap nilai tetap melewati {{ }} yang meng-escape.
     *
     * @return array{title: string|null, description: string, robots: string|null, canonical: string|null, type: string, image: string|null, jsonLd: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->resolvedTitle(),
            'description' => $this->resolvedDescription(),
            'robots' => $this->robots,
            'canonical' => $this->canonical,
            'type' => $this->type,
            'image' => $this->image,
            'jsonLd' => $this->jsonLd,
        ];
    }

    private function copy(
        ?string $headTitle = null,
        ?string $headDescription = null,
        ?string $canonical = null,
        ?string $robots = null,
        ?string $type = null,
        ?string $image = null,
        ?array $jsonLd = null,
        ?bool $brandSuffix = null,
    ): self {
        return new self(
            headTitle: $headTitle ?? $this->headTitle,
            headDescription: $headDescription ?? $this->headDescription,
            canonical: $canonical ?? $this->canonical,
            robots: $robots ?? $this->robots,
            type: $type ?? $this->type,
            image: $image ?? $this->image,
            jsonLd: $jsonLd ?? $this->jsonLd,
            brandSuffix: $brandSuffix ?? $this->brandSuffix,
        );
    }

    private function resolvedTitle(): ?string
    {
        if ($this->headTitle === null || trim($this->headTitle) === '') {
            return null;
        }

        if (! $this->brandSuffix) {
            return self::trimToWord($this->headTitle, self::TITLE_MAX);
        }

        // Suffiks brand dihitung dulu, baru judulnya dipotong. Kalau urutannya
        // dibalik, pemotongan 60 karakter bisa tepat mengenai brand dan
        // menyisakan judul yang berakhir dengan tanda pisah.
        $suffix = ' — '.self::BRAND;

        return self::trimToWord($this->headTitle, self::TITLE_MAX - mb_strlen($suffix)).$suffix;
    }

    private function resolvedDescription(): string
    {
        return self::trimToWord($this->headDescription, self::DESCRIPTION_MAX);
    }

    /**
     * Memotong di batas kata, bukan di tengah kata. Fragmen sisa yang terlalu
     * pendek ikut dibuang supaya tidak menghasilkan "oleh-oleh haj" di akhir.
     */
    private static function trimToWord(string $value, int $limit): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if ($value === '' || mb_strlen($value) <= $limit) {
            return $value;
        }

        $cut = mb_substr($value, 0, $limit);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace > (int) ($limit * 0.6)) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " \t\n\r\0\x0B,.;:-");
    }
}
