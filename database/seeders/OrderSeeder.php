<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    private const SHIPPING_CODE = [
        'Reguler' => 'reguler',
        'Kargo rombongan' => 'kargo',
        'Same day' => 'sameday',
    ];

    private const PAYMENT_CODE = [
        'Transfer BCA' => 'transfer',
        'Transfer BSI' => 'transfer',
        'Transfer Mandiri' => 'transfer',
        'QRIS' => 'qris',
        'Kartu kredit' => 'card',
    ];

    public function run(): void
    {
        $userIds = User::pluck('id', 'name');
        $shippingIds = ShippingMethod::pluck('id', 'code');
        $paymentIds = PaymentMethod::pluck('id', 'code');
        $productIds = Product::pluck('id', 'sku');

        $orders = [
            [
                'id' => 'AGF-24081', 'customer' => 'Ratna Halim', 'email' => 'ratna.halim@mail.com', 'phone' => '0812-3344-5566',
                'date' => '2026-08-18T09:12:00', 'payment' => 'Transfer BCA', 'status' => 'paid', 'channel' => 'Website',
                'note' => 'Tolong tulis: "Untuk Ibu, dari Ratna."',
                'address' => 'Jl. Dharmahusada Indah 12, Surabaya, Jawa Timur 60285',
                'shipping' => ['method' => 'Reguler', 'cost' => 22000],
                'items' => [
                    ['name' => 'Arafah Premium Box', 'sku' => 'AGF-BOX-01', 'qty' => 1, 'price' => 649000, 'art' => 'giftset'],
                    ['name' => 'Kurma Ajwa Premium 500 g', 'sku' => 'AGF-KUR-01', 'qty' => 2, 'price' => 285000, 'art' => 'kurma'],
                ],
            ],
            [
                'id' => 'AGF-24080', 'customer' => 'H. Zulkarnain', 'email' => 'zulkarnain@travelbarokah.id', 'phone' => '0813-9090-1122',
                'date' => '2026-08-18T07:40:00', 'payment' => 'Transfer BSI', 'status' => 'processing', 'channel' => 'WhatsApp',
                'note' => 'Cetak nama jamaah, file dikirim via email.',
                'address' => 'Jl. Slamet Riyadi 210, Solo, Jawa Tengah 57131',
                'shipping' => ['method' => 'Kargo rombongan', 'cost' => 15000],
                'items' => [
                    ['name' => 'Paket Salam — Souvenir Rombongan', 'sku' => 'AGF-SOU-01', 'qty' => 240, 'price' => 27500, 'art' => 'souvenir'],
                ],
            ],
            [
                'id' => 'AGF-24079', 'customer' => 'Dewi Anggraini', 'email' => 'dewi.ang@mail.com', 'phone' => '0811-2233-4455',
                'date' => '2026-08-17T16:05:00', 'payment' => 'QRIS', 'status' => 'shipped', 'channel' => 'Website',
                'note' => 'Kirim ke alamat ibu, jangan sertakan nota.',
                'address' => 'Jl. Andi Pangeran Pettarani 88, Makassar, Sulawesi Selatan 90222',
                'shipping' => ['method' => 'Reguler', 'cost' => 22000],
                'items' => [
                    ['name' => 'Family Gift Set', 'sku' => 'AGF-BOX-02', 'qty' => 1, 'price' => 1250000, 'art' => 'giftset'],
                ],
            ],
            [
                'id' => 'AGF-24078', 'customer' => 'Fajar Nugroho', 'email' => 'fajar.n@mail.com', 'phone' => '0857-1212-3434',
                'date' => '2026-08-17T11:22:00', 'payment' => 'Kartu kredit', 'status' => 'completed', 'channel' => 'Website',
                'note' => '',
                'address' => 'Jl. Cikini Raya 45, Jakarta Pusat, DKI Jakarta 10330',
                'shipping' => ['method' => 'Same day', 'cost' => 45000],
                'items' => [
                    ['name' => 'Tasbih Kayu Zaitun', 'sku' => 'AGF-TAS-01', 'qty' => 3, 'price' => 95000, 'art' => 'tasbih'],
                    ['name' => 'Madu Sidr Yaman 250 g', 'sku' => 'AGF-MAD-01', 'qty' => 1, 'price' => 320000, 'art' => 'madu'],
                ],
            ],
            [
                'id' => 'AGF-24077', 'customer' => 'Siti Maryam', 'email' => 'siti.maryam@mail.com', 'phone' => '0821-5566-7788',
                'date' => '2026-08-16T19:48:00', 'payment' => 'Transfer Mandiri', 'status' => 'pending', 'channel' => 'Website',
                'note' => '',
                'address' => 'Jl. Diponegoro 7, Bandung, Jawa Barat 40115',
                'shipping' => ['method' => 'Reguler', 'cost' => 22000],
                'items' => [
                    ['name' => 'Sajadah Travel Lipat', 'sku' => 'AGF-SAJ-01', 'qty' => 2, 'price' => 189000, 'art' => 'sajadah'],
                ],
            ],
            [
                'id' => 'AGF-24076', 'customer' => 'Abdul Rahman', 'email' => 'a.rahman@mail.com', 'phone' => '0878-4433-2211',
                'date' => '2026-08-16T10:02:00', 'payment' => 'QRIS', 'status' => 'cancelled', 'channel' => 'Website',
                'note' => 'Dibatalkan pembeli, salah alamat.',
                'address' => 'Jl. Ahmad Yani 3, Parepare, Sulawesi Selatan 91114',
                'shipping' => ['method' => 'Reguler', 'cost' => 22000],
                'items' => [
                    ['name' => 'Parfum Oud Attar 6 ml', 'sku' => 'AGF-PAR-01', 'qty' => 1, 'price' => 135000, 'art' => 'parfum'],
                ],
            ],
        ];

        foreach ($orders as $data) {
            $subtotal = array_sum(array_map(fn ($i) => $i['price'] * $i['qty'], $data['items']));
            $shippingCost = $data['shipping']['cost'];

            $order = Order::create([
                'order_number' => $data['id'],
                'user_id' => $userIds[$data['customer']] ?? null,
                'customer_name' => $data['customer'],
                'customer_email' => $data['email'],
                'customer_phone' => $data['phone'],
                'address' => $data['address'],
                'city' => '',
                'postal_code' => '00000',
                'shipping_method_id' => $shippingIds[self::SHIPPING_CODE[$data['shipping']['method']]],
                'shipping_cost' => $shippingCost,
                'payment_method_id' => $paymentIds[self::PAYMENT_CODE[$data['payment']]],
                'hide_invoice' => true,
                'status' => $data['status'],
                'channel' => $data['channel'],
                'note' => $data['note'] ?: null,
                'subtotal' => $subtotal,
                'total' => $subtotal + $shippingCost,
                'created_at' => $data['date'],
                'updated_at' => $data['date'],
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $productIds[$item['sku']] ?? null,
                    'name' => $item['name'],
                    'sku' => $item['sku'],
                    'art' => $item['art'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                ]);
            }
        }
    }
}
