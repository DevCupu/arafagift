<?php

namespace App\Support\Seo;

use App\Models\Content;
use App\Support\Phone;
use Illuminate\Support\Facades\Cache;

/**
 * Skema entitas toko untuk seluruh halaman. Ini yang menyatukan profil Google
 * Business Profile dengan situs: GBP dan on-page harus menyebut nama, alamat,
 * dan telepon yang sama persis, kalau tidak Google memperlakukannya sebagai dua
 * bisnis terpisah dan menahan peringkat lokal.
 *
 * Data diambil dari tabel settings lewat StoreSettingsCache, jadi mengubah
 * alamat di panel admin langsung mengubah structured data tanpa menyentuh kode.
 */
final class StoreJsonLd
{
    /**
     * @param  array<string, mixed>  $store
     * @return array<string, mixed>|null
     */
    public static function graph(array $store): ?array
    {
        $name = trim((string) ($store['name'] ?? ''));

        if ($name === '') {
            return null;
        }

        $address = trim((string) ($store['address'] ?? ''));
        $graph = [
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            '@id' => url('/').'#store',
            'name' => $name,
            'url' => url('/'),
            'description' => 'Toko oleh-oleh dan hadiah Haji serta Umrah: kurma premium, sajadah, tasbih, aksesori, dan gift set dengan packaging elegan.',
            'currenciesAccepted' => 'IDR',
            'paymentAccepted' => 'Transfer Bank, QRIS, E-Wallet, Kartu Kredit',
            'areaServed' => ['@type' => 'Country', 'name' => 'Indonesia'],
        ];

        if ($address !== '') {
            $graph['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressCountry' => 'ID',
            ];
        }

        $telephone = self::telephone((string) ($store['whatsapp'] ?? ''));

        if ($telephone !== null) {
            $graph['telephone'] = $telephone;
        }

        $email = trim((string) ($store['email'] ?? ''));

        if ($email !== '') {
            $graph['email'] = $email;
        }

        $social = self::socialProfiles();

        if ($social !== []) {
            $graph['sameAs'] = $social;
        }

        return $graph;
    }

    /**
     * Schema.org menuntut format E.164, sedangkan Phone::normalize dipakai
     * seluruh endpoint publik dalam format 0XXXXXXXXX.
     */
    private static function telephone(string $value): ?string
    {
        if (trim($value) === '') {
            return null;
        }

        $local = Phone::normalize($value);

        if (strlen($local) < 9) {
            return null;
        }

        return str_starts_with($local, '0') ? '+62'.substr($local, 1) : '+'.$local;
    }

    /**
     * Instagram hanya diambil dari konten home, jadi butuh satu query terpisah
     * di halaman selain home. Dicache satu jam supaya tidak menambah query ke
     * setiap render halaman.
     *
     * @return list<string>
     */
    private static function socialProfiles(): array
    {
        $url = Cache::flexible('seo-store-same-as', [600, 3600], function (): ?string {
            $value = Content::where('key', 'home')->first()?->data['instagram']['url'] ?? null;

            return is_string($value) ? trim($value) : null;
        });

        return $url === null || $url === '' ? [] : [$url];
    }
}
