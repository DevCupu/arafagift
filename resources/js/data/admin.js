export const orderStatuses = [
  { id: 'pending', label: 'Menunggu bayar', tone: 'warn' },
  { id: 'paid', label: 'Dibayar', tone: 'info' },
  { id: 'processing', label: 'Diproses', tone: 'info' },
  { id: 'shipped', label: 'Dikirim', tone: 'info' },
  { id: 'completed', label: 'Selesai', tone: 'success' },
  { id: 'cancelled', label: 'Dibatalkan', tone: 'danger' },
]

export const statusMeta = (id) =>
  orderStatuses.find((s) => s.id === id) ?? { id, label: id, tone: 'muted' }

export const orders = [
  {
    id: 'AGF-24081',
    customer: 'Ratna Halim',
    email: 'ratna.halim@mail.com',
    phone: '0812-3344-5566',
    date: '2026-08-18T09:12:00',
    payment: 'Transfer BCA',
    status: 'paid',
    channel: 'Website',
    note: 'Tolong tulis: “Untuk Ibu, dari Ratna.”',
    address: 'Jl. Dharmahusada Indah 12, Surabaya, Jawa Timur 60285',
    shipping: { method: 'Reguler', cost: 22000, courier: 'SiCepat', awb: null },
    items: [
      { name: 'Arafah Premium Box', sku: 'AGF-BOX-01', qty: 1, price: 649000, art: 'giftset' },
      { name: 'Kurma Ajwa Premium 500 g', sku: 'AGF-KUR-01', qty: 2, price: 285000, art: 'kurma' },
    ],
  },
  {
    id: 'AGF-24080',
    customer: 'H. Zulkarnain',
    email: 'zulkarnain@travelbarokah.id',
    phone: '0813-9090-1122',
    date: '2026-08-18T07:40:00',
    payment: 'Transfer BSI',
    status: 'processing',
    channel: 'WhatsApp',
    note: 'Cetak nama jamaah, file dikirim via email.',
    address: 'Jl. Slamet Riyadi 210, Solo, Jawa Tengah 57131',
    shipping: { method: 'Kargo rombongan', cost: 15000, courier: 'JNE Trucking', awb: null },
    items: [
      { name: 'Paket Salam — Souvenir Rombongan', sku: 'AGF-SOU-01', qty: 240, price: 27500, art: 'souvenir' },
    ],
  },
  {
    id: 'AGF-24079',
    customer: 'Dewi Anggraini',
    email: 'dewi.ang@mail.com',
    phone: '0811-2233-4455',
    date: '2026-08-17T16:05:00',
    payment: 'QRIS',
    status: 'shipped',
    channel: 'Website',
    note: 'Kirim ke alamat ibu, jangan sertakan nota.',
    address: 'Jl. Andi Pangeran Pettarani 88, Makassar, Sulawesi Selatan 90222',
    shipping: { method: 'Reguler', cost: 22000, courier: 'SiCepat', awb: 'SC8842190022' },
    items: [{ name: 'Family Gift Set', sku: 'AGF-BOX-02', qty: 1, price: 1250000, art: 'giftset' }],
  },
  {
    id: 'AGF-24078',
    customer: 'Fajar Nugroho',
    email: 'fajar.n@mail.com',
    phone: '0857-1212-3434',
    date: '2026-08-17T11:22:00',
    payment: 'Kartu kredit',
    status: 'completed',
    channel: 'Website',
    note: '',
    address: 'Jl. Cikini Raya 45, Jakarta Pusat, DKI Jakarta 10330',
    shipping: { method: 'Same day', cost: 45000, courier: 'Grab', awb: 'GRB-771204' },
    items: [
      { name: 'Tasbih Kayu Zaitun', sku: 'AGF-TAS-01', qty: 3, price: 95000, art: 'tasbih' },
      { name: 'Madu Sidr Yaman 250 g', sku: 'AGF-MAD-01', qty: 1, price: 320000, art: 'madu' },
    ],
  },
  {
    id: 'AGF-24077',
    customer: 'Siti Maryam',
    email: 'siti.maryam@mail.com',
    phone: '0821-5566-7788',
    date: '2026-08-16T19:48:00',
    payment: 'Transfer Mandiri',
    status: 'pending',
    channel: 'Website',
    note: '',
    address: 'Jl. Diponegoro 7, Bandung, Jawa Barat 40115',
    shipping: { method: 'Reguler', cost: 22000, courier: 'SiCepat', awb: null },
    items: [{ name: 'Sajadah Travel Lipat', sku: 'AGF-SAJ-01', qty: 2, price: 189000, art: 'sajadah' }],
  },
  {
    id: 'AGF-24076',
    customer: 'Abdul Rahman',
    email: 'a.rahman@mail.com',
    phone: '0878-4433-2211',
    date: '2026-08-16T10:02:00',
    payment: 'QRIS',
    status: 'cancelled',
    channel: 'Website',
    note: 'Dibatalkan pembeli, salah alamat.',
    address: 'Jl. Ahmad Yani 3, Parepare, Sulawesi Selatan 91114',
    shipping: { method: 'Reguler', cost: 22000, courier: 'SiCepat', awb: null },
    items: [{ name: 'Parfum Oud Attar 6 ml', sku: 'AGF-PAR-01', qty: 1, price: 135000, art: 'parfum' }],
  },
]

export const orderTotal = (order) =>
  order.items.reduce((sum, i) => sum + i.price * i.qty, 0) + order.shipping.cost

export const customers = [
  {
    id: 1, name: 'Ratna Halim', email: 'ratna.halim@mail.com', phone: '0812-3344-5566',
    city: 'Surabaya', orders: 6, spent: 4820000, lastOrder: '2026-08-18', joined: '2025-03-11',
    tag: 'Pelanggan tetap',
  },
  {
    id: 2, name: 'H. Zulkarnain', email: 'zulkarnain@travelbarokah.id', phone: '0813-9090-1122',
    city: 'Solo', orders: 11, spent: 38400000, lastOrder: '2026-08-18', joined: '2024-11-02',
    tag: 'Travel / rombongan',
  },
  {
    id: 3, name: 'Dewi Anggraini', email: 'dewi.ang@mail.com', phone: '0811-2233-4455',
    city: 'Makassar', orders: 3, spent: 2145000, lastOrder: '2026-08-17', joined: '2025-09-20',
    tag: 'Pelanggan tetap',
  },
  {
    id: 4, name: 'Fajar Nugroho', email: 'fajar.n@mail.com', phone: '0857-1212-3434',
    city: 'Jakarta', orders: 2, spent: 950000, lastOrder: '2026-08-17', joined: '2026-01-14',
    tag: 'Baru',
  },
  {
    id: 5, name: 'Siti Maryam', email: 'siti.maryam@mail.com', phone: '0821-5566-7788',
    city: 'Bandung', orders: 1, spent: 400000, lastOrder: '2026-08-16', joined: '2026-08-16',
    tag: 'Baru',
  },
  {
    id: 6, name: 'Abdul Rahman', email: 'a.rahman@mail.com', phone: '0878-4433-2211',
    city: 'Parepare', orders: 4, spent: 1680000, lastOrder: '2026-08-16', joined: '2025-06-30',
    tag: 'Pelanggan tetap',
  },
]

// Penjualan 14 hari terakhir (juta rupiah)
export const salesSeries = [
  { label: '5 Ags', value: 6.4 }, { label: '6', value: 7.1 }, { label: '7', value: 5.2 },
  { label: '8', value: 8.9 }, { label: '9', value: 12.4 }, { label: '10', value: 10.1 },
  { label: '11', value: 9.3 }, { label: '12', value: 11.8 }, { label: '13', value: 14.6 },
  { label: '14', value: 13.2 }, { label: '15', value: 16.9 }, { label: '16', value: 15.4 },
  { label: '17', value: 18.2 }, { label: '18 Ags', value: 21.7 },
]

export const topProducts = [
  { name: 'Arafah Premium Box', sold: 128, revenue: 83072000, share: 100 },
  { name: 'Kurma Ajwa Premium 500 g', sold: 214, revenue: 60990000, share: 73 },
  { name: 'Paket Salam — Rombongan', sold: 940, revenue: 25850000, share: 31 },
  { name: 'Sajadah Travel Lipat', sold: 96, revenue: 18144000, share: 22 },
  { name: 'Tasbih Kayu Zaitun', sold: 152, revenue: 14440000, share: 17 },
]

export const dashboardStats = [
  { label: 'Penjualan hari ini', value: 'Rp 21,7 jt', delta: '+18,4%', trend: 'up', note: 'vs kemarin' },
  { label: 'Pesanan', value: '34', delta: '+6', trend: 'up', note: '5 menunggu bayar' },
  { label: 'Produk terjual', value: '312', delta: '+41', trend: 'up', note: 'termasuk rombongan' },
  { label: 'Rata-rata pesanan', value: 'Rp 638 rb', delta: '−2,1%', trend: 'down', note: 'vs pekan lalu' },
]

export const promotions = [
  { code: 'PULANGHAJI', type: 'Potongan 10%', usage: '128 / 500', period: '1–31 Ags 2026', status: 'active' },
  { code: 'ROMBONGAN50', type: 'Potongan Rp 50.000', usage: '12 / 100', period: '1 Ags – 30 Sep 2026', status: 'active' },
  { code: 'GRATISONGKIR', type: 'Gratis ongkir reguler', usage: '340 / 340', period: '1–15 Ags 2026', status: 'ended' },
]
