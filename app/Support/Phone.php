<?php

namespace App\Support;

final class Phone
{
    /**
     * Normalisasi nomor telepon Indonesia: buang tanda baca/spasi, ubah awalan 62 menjadi 0.
     * Format hasil selalu 0XXXXXXXXX agar konsisten antar endpoint publik.
     */
    public static function normalize(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($digits) > 9 && str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }

        return $digits;
    }
}
