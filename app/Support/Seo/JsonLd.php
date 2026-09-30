<?php

namespace App\Support\Seo;

final class JsonLd
{
    /**
     * Flag HEX_* mencegah nilai dari database menutup tag script lebih awal dan
     * menyuntikkan markup ke dalam halaman. Semua JSON-LD di aplikasi ini
     * melewati fungsi ini, jadi tidak ada jalur yang bisa lupa escape.
     *
     * Argument nullable karena sumber graf (misalnya entitas toko saat tabel
     * settings masih kosong) bisa tidak menghasilkan apa pun. Signature yang
     * ketat di sini pernah mengubah halaman 404 menjadi 500.
     *
     * @param  array<string, mixed>|null  $graph
     */
    public static function encode(?array $graph): ?string
    {
        if ($graph === null) {
            return null;
        }

        $json = json_encode(
            $graph,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        );

        return $json === false ? null : $json;
    }
}
