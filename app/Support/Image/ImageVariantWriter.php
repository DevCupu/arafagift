<?php

namespace App\Support\Image;

use RuntimeException;

/**
 * Menghasilkan file gambar hasil kompresi. Semua method mengembalikan byte
 * mentah, bukan menulis ke disk, supaya pemanggil yang memutuskan mau
 * disimpan ke public/ (statis, ikut ter-commit ke git) atau ke disk "public"
 * Storage (hasil upload admin, tidak masuk git).
 *
 * Format yang benar-benar didukung dibaca dari GD yang benar-benar terpasang
 * di server. Server produksi bisa saja punya libgd tanpa AVIF, dan class ini
 * diam-diam melewati format yang tidak ada alih-alih menggagalkan seluruh
 * halaman.
 */
final class ImageVariantWriter
{
    public const WEBP_QUALITY = 72;

    public const AVIF_QUALITY = 55;

    public const JPEG_QUALITY = 82;

    /**
     * AVIF lambat untuk di-encode dan selisih ukuran terhadap speed 8 hampir
     * tidak terlihat karena hasilnya tetap dikompres ulang ke WebP sebelum
     * dikirim ke browser. 8 dipakai supaya app:optimize-images tidak menahan
     * deploy.
     */
    public const AVIF_SPEED = 8;

    /**
     * Warna latar untuk versi JPEG. Format ini tidak punya alpha, dan hero
     * homepage berlatar hijau tua, jadi transparan yang dibuang tidak terlihat
     * sebagai kotak putih.
     */
    private const JPEG_BACKGROUND = [8, 32, 22];

    /**
     * Format untuk <picture>: AVIF dulu, WebP sebagai jaring pengaman untuk
     * browser yang sudah mendukung <picture> tapi belum AVIF.
     *
     * @return array<string, int> nama format => kualitas
     */
    public static function webFormats(): array
    {
        $formats = [];

        if (function_exists('imageavif')) {
            $formats['avif'] = self::AVIF_QUALITY;
        }

        if (function_exists('imagewebp')) {
            $formats['webp'] = self::WEBP_QUALITY;
        }

        return $formats;
    }

    /**
     * Ladder responsif untuk satu sumber gambar.
     *
     * @param  list<int>  $widths  lebar yang diminta; lebar yang melebihi
     *                             ukuran sumber dilewati supaya tidak ada
     *                             upscaling
     * @param  int|null  $quality  menimpa kualitas bawaan per format
     * @return array<string, array<int, string>> format => [lebar => byte]
     */
    public static function ladder(string $sourcePath, array $widths, ?int $quality = null): array
    {
        $source = self::open($sourcePath);

        try {
            $sourceWidth = imagesx($source);
            $ladder = [];

            foreach (self::webFormats() as $format => $defaultQuality) {
                $variants = [];

                foreach ($widths as $requestedWidth) {
                    $width = (int) $requestedWidth;

                    if ($width < 1 || $width > $sourceWidth) {
                        continue;
                    }

                    $resized = self::resize($source, $width);

                    try {
                        $variants[$width] = self::encode($resized, $format, $quality ?? $defaultQuality);
                    } finally {
                        imagedestroy($resized);
                    }
                }

                if ($variants !== []) {
                    $ladder[$format] = $variants;
                }
            }

            return $ladder;
        } finally {
            imagedestroy($source);
        }
    }

    /**
     * Potong ke rasio social preview lalu encode ulang. Dipakai untuk og:image
     * yang butuh rasio sekitar 1200x630, bukan rasio asli hero yang sangat
     * panoramik sehingga hanya menghasilkan strip tipis kalau dipaksakan.
     *
     * @return array<string, string> format => byte
     */
    public static function crop(string $sourcePath, int $width, int $height, float $focalX = 0.5, float $focalY = 0.5): array
    {
        $source = self::open($sourcePath);

        try {
            $cropped = self::cropToRatio($source, $width, $height, $focalX, $focalY);

            try {
                $encoded = [];

                foreach (self::webFormats() as $format => $quality) {
                    $encoded[$format] = self::encode($cropped, $format, $quality);
                }

                $encoded['jpeg'] = self::encode($cropped, 'jpeg', self::JPEG_QUALITY);

                return $encoded;
            } finally {
                imagedestroy($cropped);
            }
        } finally {
            imagedestroy($source);
        }
    }

    /**
     * Versi JPEG dari gambar asli, hanya diperkecil kalau melebihi batas. Ini
     * yang jadi fallback <img> di dalam <picture>.
     */
    public static function fallback(string $sourcePath, int $maxWidth): string
    {
        $source = self::open($sourcePath);

        try {
            $width = imagesx($source);
            $scaled = $width > $maxWidth ? self::resize($source, $maxWidth) : $source;

            try {
                return self::encode($scaled, 'jpeg', self::JPEG_QUALITY);
            } finally {
                if ($scaled !== $source) {
                    imagedestroy($scaled);
                }
            }
        } finally {
            imagedestroy($source);
        }
    }

    private static function open(string $path): \GdImage
    {
        $image = @imagecreatefromstring((string) file_get_contents($path));

        if ($image === false) {
            throw new RuntimeException(sprintf('GD tidak bisa membuka gambar: %s', basename($path)));
        }

        return $image;
    }

    private static function resize(\GdImage $source, int $width): \GdImage
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $height = (int) max(1, round($width * $sourceHeight / $sourceWidth));

        return self::resample($source, $width, $height, 0, 0, $sourceWidth, $sourceHeight);
    }

    private static function cropToRatio(\GdImage $source, int $width, int $height, float $focalX, float $focalY): \GdImage
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetRatio = $width / $height;

        if ($sourceWidth / $sourceHeight > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($cropHeight * $targetRatio);
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($cropWidth / $targetRatio);
        }

        $cropWidth = min($cropWidth, $sourceWidth);
        $cropHeight = min($cropHeight, $sourceHeight);

        // Titik fokus menentukan bagian mana yang dipertahankan saat dipotong.
        $originX = (int) round(($sourceWidth - $cropWidth) * max(0.0, min(1.0, $focalX)));
        $originY = (int) round(($sourceHeight - $cropHeight) * max(0.0, min(1.0, $focalY)));

        return self::resample($source, $width, $height, $originX, $originY, $cropWidth, $cropHeight);
    }

    private static function resample(\GdImage $source, int $width, int $height, int $originX, int $originY, int $cropWidth, int $cropHeight): \GdImage
    {
        $target = imagecreatetruecolor($width, $height);
        imagealphablending($target, false);
        imagesavealpha($target, true);

        // Transparan dulu, supaya bagian di luar area potong tidak terisi dengan
        // warna acak dari halaman sebelumnya.
        imagefilledrectangle($target, 0, 0, $width, $height, imagecolorallocatealpha($target, 0, 0, 0, 127));

        imagecopyresampled($target, $source, 0, 0, $originX, $originY, $width, $height, $cropWidth, $cropHeight);

        return $target;
    }

    private static function encode(\GdImage $image, string $format, int $quality): string
    {
        $target = $format === 'jpeg' ? self::flatten($image) : $image;

        try {
            ob_start();

            $ok = match ($format) {
                'avif' => imageavif($target, null, $quality, self::AVIF_SPEED),
                'webp' => imagewebp($target, null, $quality),
                'jpeg' => imagejpeg($target, null, $quality),
                default => throw new RuntimeException(sprintf('Format tidak dikenal: %s', $format)),
            };

            $bytes = (string) ob_get_clean();
        } finally {
            if ($target !== $image) {
                imagedestroy($target);
            }
        }

        if ($ok === false || $bytes === '') {
            throw new RuntimeException(sprintf('GD gagal encode ke %s', $format));
        }

        return $bytes;
    }

    /**
     * Tempel alpha ke latar solid. Tanpa ini bagian transparan dibaca sebagai
     * hitam oleh sebagian build GD.
     */
    private static function flatten(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $flat = imagecreatetruecolor($width, $height);
        imagefilledrectangle($flat, 0, 0, $width, $height, imagecolorallocate($flat, ...self::JPEG_BACKGROUND));

        imagealphablending($flat, true);
        imagecopy($flat, $image, 0, 0, 0, 0, $width, $height);

        return $flat;
    }
}
