<?php

namespace App\Support\Image;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Menyimpan foto hero yang diunggah dari panel admin.
 *
 * Sebelumnya file asli apa adanya masuk ke disk publik dan hanya URL absolutnya
 * yang disimpan. Tiga masalah ikut diselesaikan di sini:
 *
 * 1. Foto 4 MB tetap diunduh mentah oleh setiap pengunjung, padahal hero itu
 *    LCP. Sekarang yang disimpan master terkompresi plus ladder WebP/AVIF,
 *    jadi ResponsiveImage punya srcset untuk <picture>.
 * 2. URL absolut dari Storage::url() ikut membawa APP_URL ke dalam isi
 *    database. Kalau APP_URL produksi salah atau berubah, foto hero ikut
 *    menunjuk ke host yang salah. Yang disimpan sekarang path relatif.
 * 3. File lama tidak pernah dihapus, jadi setiap penggantian hero meninggalkan
 *    foto 4 MB yang tidak terpakai di storage.
 */
final class HeroImageStore
{
    /**
     * Batas sisi terpanjang master. Di atas ini tidak ada yang perlu
     * disimpan karena layar monitor besar pun tidak pernah memakai lebih dari
     * lebar variant terbesar di HeroImage::WIDTHS.
     */
    public const MAX_WIDTH = 2000;

    private const DIRECTORY = 'content';

    private const DISK = 'public';

    /**
     * @return string path relatif untuk disimpan di contents.data.hero.image
     */
    public static function store(UploadedFile $file): string
    {
        $source = $file->getRealPath();

        if ($source === false || ! is_file($source)) {
            throw new RuntimeException('Berkas hero tidak bisa dibaca.');
        }

        // Nama acak, bukan nama asli berkas unggahan. Kalau nama diturunkan dari
        // isi file, unggahan ulang foto yang sama persis akan mendapat path yang
        // sama dan browser akan terus menyajikan versi lama dari cache.
        $stem = self::DIRECTORY.'/hero-'.Str::random(10);

        $written = [];

        try {
            $written[] = self::put($stem.'.jpg', ImageVariantWriter::fallback($source, self::MAX_WIDTH));

            foreach (ImageVariantWriter::ladder($source, HeroImage::WIDTHS) as $format => $variants) {
                foreach ($variants as $width => $bytes) {
                    $written[] = self::put($stem.'-'.$width.'.'.$format, $bytes);
                }
            }
        } catch (\Throwable $e) {
            self::deletePaths($written);

            throw $e;
        }

        return $stem.'.jpg';
    }

    /**
     * Menghapus master beserta seluruh variant-nya. Dipanggil saat hero diganti
     * atau dikembalikan ke foto bawaan, supaya disk publik tidak menumpuk
     * foto yang tidak lagi dirujuk siapa pun.
     */
    public static function remove(?string $reference): void
    {
        $path = ResponsiveImage::storageRelative($reference);

        if ($path === null) {
            return;
        }

        $disk = Storage::disk(self::DISK);
        $directory = trim(dirname(str_replace('\\', '/', $path)), '.');

        $paths = [$path];

        if ($directory !== '') {
            $prefix = pathinfo($path, PATHINFO_FILENAME).'-';

            foreach ($disk->files($directory) as $file) {
                if (str_starts_with(pathinfo($file, PATHINFO_FILENAME).'-', $prefix)) {
                    $paths[] = $file;
                }
            }
        }

        self::deletePaths($paths);
    }

    private static function put(string $path, string $bytes): string
    {
        Storage::disk(self::DISK)->put($path, $bytes);

        return $path;
    }

    /**
     * @param  list<string>  $paths
     */
    private static function deletePaths(array $paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths === []) {
            return;
        }

        Storage::disk(self::DISK)->delete($paths);
    }
}
