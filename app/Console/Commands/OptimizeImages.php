<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;

class OptimizeImages extends Command
{
    protected $signature = 'app:optimize-images {--dry-run : Cek ukuran tanpa menulis file}';

    protected $description = 'Kompres & resize WebP lokal (hero + section homepage) pakai GD. Aman dijalankan ulang.';

    private const QUALITY = 75;

    /** sesuaikan kualitas per file (hero adalah LCP — prioritaskan ukuran). */
    private const QUALITY_OVERRIDES = [
        'hero.webp' => 68,
    ];

    private array $targets = [];

    public function __construct()
    {
        parent::__construct();

        $this->targets = [
            base_path('resources/js/assets/hero.webp') => 1280,
            public_path('images/assets/gift-worth-remembering.webp') => 800,
            public_path('images/assets/section-lebih-dari-oleh-oleh.webp') => 1024,
            public_path('images/assets/souvenir-satu-rombongan.webp') => 768,
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

        $dryRun = $this->option('dry-run');

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
            imagewebp($optimized, null, self::QUALITY_OVERRIDES[basename($path)] ?? self::QUALITY);
            $buffer = (string) ob_get_clean();
            imagedestroy($optimized);

            // Jangan simpan hasil yang justru lebih besar.
            if (strlen($buffer) >= $before) {
                $this->line(sprintf('%-42s sudah optimal (%s)', basename($path), self::human($before)));

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf('%-42s %s → %s  (-%s)', basename($path), self::human($before), self::human(strlen($buffer)), sprintf('%.0f%%', (1 - strlen($buffer) / max($before, 1)) * 100)));

                continue;
            }

            if (file_put_contents($path, $buffer) === false) {
                throw new RuntimeException(sprintf('Gagal menulis %s', $path));
            }

            $this->info(sprintf('%-42s %s → %s  (-%s)  [%dx%d]', basename($path), self::human($before), self::human(strlen($buffer)), sprintf('%.0f%%', (1 - strlen($buffer) / max($before, 1)) * 100), $newWidth, $newHeight));
        }

        $this->newLine();
        $this->info($dryRun ? 'Selesai (dry-run).' : 'Selesai. Jalankan `npm run build` agar hero.webp yang di-import ikut diperbarui.');

        return self::SUCCESS;
    }

    private static function human(int $bytes): string
    {
        return $bytes >= 1048576
            ? sprintf('%.2f MB', $bytes / 1048576)
            : sprintf('%.1f KB', $bytes / 1024);
    }
}