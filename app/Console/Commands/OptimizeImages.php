<?php

namespace App\Console\Commands;

use App\Support\Image\HeroImage;
use App\Support\Image\ImageVariantWriter;
use Illuminate\Console\Command;
use RuntimeException;

class OptimizeImages extends Command
{
    protected $signature = 'app:optimize-images {--dry-run : Cek ukuran tanpa menulis file}';

    protected $description = 'Kompres & resize WebP lokal (hero + section homepage) pakai GD. Aman dijalankan ulang.';

    private const QUALITY = 75;

    private array $targets = [];

    public function __construct()
    {
        parent::__construct();

        // Foto section homepage bukan LCP, jadi cukup dikompres ulang di tempat
        // dengan lebar tetap. Hero punya jalur terpisah di optimizeHero()
        // karena butuh ladder responsif, bukan satu berkas.
        $this->targets = [
            public_path('images/assets/gift-worth-remembering.webp') => 800,
            public_path('images/assets/section-lebih-dari-oleh-oleh.webp') => 1024,
            public_path('images/assets/souvenir-satu-rombongan.webp') => 768,
            public_path('images/assets/img-1.webp') => 768,
            public_path('images/assets/img-2.webp') => 768,
            public_path('images/assets/img-3.webp') => 768,
            public_path('images/assets/img-4.webp') => 768,
            public_path('images/assets/perjalanan-pulang-membawa-cerita.webp') => 768,
        ];
    }

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('Ekstensi GD tidak tersedia; lewati kompresi gambar.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->optimizeSections($dryRun);
        $this->optimizeHero($dryRun);

        $this->newLine();
        $this->info($dryRun
            ? 'Selesai (dry-run).'
            : 'Selesai. Variant hero ikut ter-commit di public/images/assets, jadi tidak perlu build ulang.');

        return self::SUCCESS;
    }

    private function optimizeSections(bool $dryRun): void
    {
        foreach ($this->targets as $path => $maxWidth) {
            if (! is_file($path)) {
                $this->warn(sprintf('Lewati (tidak ada): %s', basename($path)));

                continue;
            }

            $before = filesize($path);

            try {
                $image = imagecreatefromwebp($path);
            } catch (\Throwable $e) {
                $this->warn(sprintf('Gagal baca %s: %s', basename($path), $e->getMessage()));

                continue;
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $newWidth = min($width, $maxWidth);
            $scale = $newWidth / $width;
            $newHeight = (int) round($height * $scale);

            $optimized = $image;
            if ($newWidth < $width) {
                $optimized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($optimized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
            }

            $buffer = '';
            ob_start();
            imagewebp($optimized, null, self::QUALITY);
            $buffer = (string) ob_get_clean();
            imagedestroy($optimized);

            $this->report($path, $before, $buffer, $newWidth, $newHeight, $dryRun);
        }
    }

    /**
     * Hero homepage adalah LCP situs dan sebelumnya dilayani sebagai satu PNG
     * 1,9 MB untuk semua perangkat, termasuk ponsel. Sekarang PNG sumbernya
     * tinggal di resources/images sebagai bahan mentah, dan jadis yang lolos
     * adalah ladder WebP plus AVIF, satu JPEG fallback untuk <img> di dalam
     * <picture>, dan satu og:image terpotong 1200x630 untuk share link.
     */
    private function optimizeHero(bool $dryRun): void
    {
        $source = HeroImage::sourcePath();

        if (! is_file($source)) {
            $this->warn(sprintf('Lewati hero (tidak ada): %s', HeroImage::SOURCE));

            return;
        }

        $directory = public_path('images/assets');
        $stem = pathinfo(HeroImage::FALLBACK, PATHINFO_FILENAME);

        if (! is_dir($directory)) {
            $this->warn(sprintf('Lewati hero (folder tujuan tidak ada): %s', $directory));

            return;
        }

        $this->line('Hero:');
        $this->writeLadder($source, $directory, $stem, HeroImage::WIDTHS, $dryRun);

        // Fallback <img> di dalam <picture>. Browser yang sudah bisa
        // <picture> sudah pasti bisa WebP, tapi JPEG hanya ditulis sekali dan
        // tetap aman untuk apa pun yang membaca URL-nya secara langsung.
        $this->write(
            $directory.'/'.$stem.'.jpg',
            ImageVariantWriter::fallback($source, max(HeroImage::WIDTHS)),
            $dryRun,
        );

        $this->writeSocial($source, $dryRun);
    }

    /**
     * @param  list<int>  $widths
     */
    private function writeLadder(string $source, string $directory, string $stem, array $widths, bool $dryRun): void
    {
        foreach (ImageVariantWriter::ladder($source, $widths) as $format => $variants) {
            foreach ($variants as $width => $bytes) {
                $this->write($directory.'/'.$stem.'-'.$width.'.'.$format, $bytes, $dryRun);
            }
        }
    }

    private function writeSocial(string $source, bool $dryRun): void
    {
        $encoded = ImageVariantWriter::crop(
            $source,
            HeroImage::SOCIAL_WIDTH,
            HeroImage::SOCIAL_HEIGHT,
            HeroImage::SOCIAL_FOCAL_X,
            HeroImage::SOCIAL_FOCAL_Y,
        );

        // Hanya JPEG yang dipakai untuk og:image. Facebook dan WhatsApp
        // lebih konsisten dengan JPEG dan tetap jauh lebih kecil daripada PNG
        // asli yang sekarang yang dibagi ke setiap share link.
        $this->write(public_path(HeroImage::SOCIAL), $encoded['jpeg'], $dryRun);
    }

    private function write(string $path, string $bytes, bool $dryRun): void
    {
        $label = basename($path);
        $before = is_file($path) ? filesize($path) : null;

        if ($dryRun) {
            $this->line(sprintf(
                '  %-40s %s  (%s)',
                $label,
                $before === null ? 'baru' : self::human($before).' -> '.self::human(strlen($bytes)),
                self::human(strlen($bytes)),
            ));

            return;
        }

        if (file_put_contents($path, $bytes) === false) {
            throw new RuntimeException(sprintf('Gagal menulis %s', $path));
        }

        $this->info(sprintf(
            '  %-40s %s  (%s)',
            $label,
            $before === null ? 'baru' : self::human($before).' -> '.self::human(strlen($bytes)),
            self::human(strlen($bytes)),
        ));
    }

    private function report(string $path, int $before, string $buffer, int $width, int $height, bool $dryRun): void
    {
        // Jangan simpan hasil yang justru lebih besar.
        if (strlen($buffer) >= $before) {
            $this->line(sprintf('%-42s sudah optimal (%s)', basename($path), self::human($before)));

            return;
        }

        $summary = sprintf(
            '%s → %s  (-%s)',
            self::human($before),
            self::human(strlen($buffer)),
            sprintf('%.0f%%', (1 - strlen($buffer) / max($before, 1)) * 100),
        );

        if (! $dryRun && file_put_contents($path, $buffer) === false) {
            throw new RuntimeException(sprintf('Gagal menulis %s', $path));
        }

        if (! $dryRun) {
            $summary .= sprintf('  [%dx%d]', $width, $height);
        }

        $dryRun ? $this->line(sprintf('%-42s %s', basename($path), $summary)) : $this->info(sprintf('%-42s %s', basename($path), $summary));
    }

    private static function human(int $bytes): string
    {
        return $bytes >= 1048576
            ? sprintf('%.2f MB', $bytes / 1048576)
            : sprintf('%.1f KB', $bytes / 1024);
    }
}
