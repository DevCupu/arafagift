<?php

namespace App\Support\Image;

use Illuminate\Support\Facades\Storage;

/**
 * Optional derivatives for product and category photos. The original image and
 * its database reference are never changed.
 */
final class CatalogImage
{
    public const WIDTHS = [320, 640, 960, 1280];

    public static function responsive(?string $reference): ?array
    {
        return $reference ? ResponsiveImage::make($reference, self::WIDTHS)?->toArray() : null;
    }

    /**
     * Generate variants only for local catalog uploads and bundled catalog
     * photos. Returns the number of new files written.
     */
    public static function generate(string $reference, bool $dryRun = false): int
    {
        if (str_starts_with($reference, '/images/catalog/')) {
            $source = public_path(ltrim($reference, '/'));
            $target = static fn (string $name): string => dirname($source).DIRECTORY_SEPARATOR.$name;
        } elseif (str_starts_with($reference, 'products/') || str_starts_with($reference, 'categories/')) {
            $disk = Storage::disk('public');
            $source = $disk->path($reference);
            $target = static fn (string $name): string => dirname($source).DIRECTORY_SEPARATOR.$name;
        } else {
            return 0;
        }

        if (! is_file($source)) {
            return 0;
        }

        $stem = pathinfo($source, PATHINFO_FILENAME);
        $written = 0;
        $social = $target($stem.'-social.jpg');
        $size = @getimagesize($source);

        if ($size === false) {
            throw new \RuntimeException('Gambar katalog tidak bisa dibaca.');
        }

        $widths = array_values(array_filter(self::WIDTHS, static function (int $width) use ($size, $target, $stem): bool {
            if ($width > $size[0]) {
                return false;
            }

            foreach (ImageVariantWriter::webFormats() as $format => $_quality) {
                if (! is_file($target($stem.'-'.$width.'.'.$format))) {
                    return true;
                }
            }

            return false;
        }));

        if ($widths === [] && is_file($social)) {
            return 0;
        }

        foreach ($widths === [] ? [] : ImageVariantWriter::ladder($source, $widths) as $format => $variants) {
            foreach ($variants as $width => $bytes) {
                $path = $target($stem.'-'.$width.'.'.$format);

                if (is_file($path)) {
                    continue;
                }

                if (! $dryRun && file_put_contents($path, $bytes) === false) {
                    throw new \RuntimeException('Gagal menyimpan varian gambar katalog.');
                }

                $written++;
            }
        }

        // A smaller JPEG gives social preview crawlers a widely supported
        // format without making them download a multi-megabyte PNG original.
        if (! is_file($social)) {
            $bytes = ImageVariantWriter::fallback($source, 1200);

            if (! $dryRun && file_put_contents($social, $bytes) === false) {
                throw new \RuntimeException('Gagal menyimpan gambar preview katalog.');
            }

            $written++;
        }

        return $written;
    }

    public static function socialUrl(?string $reference): ?string
    {
        if (! $reference) {
            return null;
        }

        $image = ResponsiveImage::make($reference, []);
        if ($image === null) {
            return null;
        }

        $url = $image->fallback;
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        if ($extension === '') {
            return $url;
        }

        $social = substr($url, 0, -strlen($extension) - 1).'-social.jpg';

        if (str_starts_with($reference, '/images/catalog/')) {
            return is_file(public_path(ltrim($social, '/'))) ? $social : $url;
        }

        if (str_starts_with($reference, 'products/') || str_starts_with($reference, 'categories/')) {
            $path = dirname($reference).'/'.pathinfo($reference, PATHINFO_FILENAME).'-social.jpg';

            return Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : $url;
        }

        return $url;
    }
}
