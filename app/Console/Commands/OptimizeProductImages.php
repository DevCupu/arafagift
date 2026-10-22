<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Support\Image\CatalogImage;
use Illuminate\Console\Command;
use Throwable;

class OptimizeProductImages extends Command
{
    protected $signature = 'app:optimize-product-images {--dry-run : Tampilkan jumlah varian tanpa menulis file}';

    protected $description = 'Buat varian foto produk dan kategori, tanpa mengubah gambar asli atau data katalog.';

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->warn('Ekstensi GD tidak tersedia; gambar asli tetap dipakai.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $products = 0;
        $variants = 0;
        $failed = 0;

        foreach ([Product::class, Category::class] as $model) {
            foreach ($model::query()->whereNotNull('image')->select('image')->distinct()->cursor() as $item) {
                try {
                    $count = CatalogImage::generate($item->image, $dryRun);
                    $products++;
                    $variants += $count;

                    if ($count > 0) {
                        $this->line(sprintf('%s: %d varian%s', $item->image, $count, $dryRun ? ' akan dibuat' : ' dibuat'));
                    }
                } catch (Throwable $e) {
                    $failed++;
                    $this->warn(sprintf('%s: %s', $item->image, $e->getMessage()));
                }
            }
        }

        $this->info(sprintf('%d gambar diperiksa, %d varian%s, %d gagal. Data katalog dan gambar asli tidak diubah.',
            $products, $variants, $dryRun ? ' direncanakan' : ' dibuat', $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
