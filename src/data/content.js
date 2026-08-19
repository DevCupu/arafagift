// Konten homepage. Semua field di sini bisa diubah lewat Admin → Content,
// jadi tidak ada teks homepage yang di-hardcode di dalam component.

export const homeContent = {
  announcement: 'Gratis kartu ucapan tulis tangan untuk setiap pesanan hadiah',
  hero: {
    eyebrow: 'Oleh-oleh Umrah & Hajj',
    headline: 'Hadiah dari Tanah Suci,\nuntuk hati yang dekat.',
    sub: 'Koleksi yang dipilih dengan hati — untuk keluarga, sahabat, dan orang-orang terkasih yang menunggu di rumah.',
    cta: { label: 'Jelajahi Koleksi', to: '/koleksi' },
    ctaSecondary: { label: 'Lihat Gift Set', to: '/koleksi/gift-set' },
    note: 'Dikirim dari Jakarta • Kartu ucapan gratis',
  },
  signature: {
    eyebrow: 'Signature',
    title: 'A Gift Worth Remembering',
    body: 'Satu box yang tidak perlu dijelaskan saat diberikan. Isinya kami susun supaya terasa lengkap — ada yang dinikmati bersama, ada yang dipakai setiap hari.',
    productSlug: 'arafah-premium-box',
    cta: { label: 'Discover the Collection', to: '/produk/arafah-premium-box' },
  },
  story: {
    eyebrow: 'Cerita kami',
    title: 'Setiap perjalanan pulang membawa cerita.',
    body: [
      'ArafahGift dimulai dari satu koper yang selalu kurang muat. Pulang dari tanah suci, yang paling sulit bukan perjalanannya — tapi memilih apa yang pantas dibawa untuk orang di rumah.',
      'Kami mengurus bagian itu: mencari yang benar-benar bagus, mengemasnya dengan rapi, dan menuliskan kartunya. Sisanya, biar doa dan cerita Anda yang bicara.',
    ],
    signature: 'Tim ArafahGift, Jakarta',
  },
  bulk: {
    eyebrow: 'Rombongan',
    title: 'Souvenir untuk satu rombongan?',
    sub: 'Pesan dalam jumlah banyak dengan packaging yang tetap cantik. Kami cetak nama jamaah dan tanggal keberangkatan di setiap kartu.',
    points: [
      'Mulai 50 pcs, harga menyesuaikan jumlah',
      'Custom kartu, logo travel, dan pita',
      'Produksi 3–5 hari kerja, kirim ke satu atau banyak alamat',
    ],
    cta: { label: 'Konsultasi via WhatsApp', href: 'https://wa.me/6281234567890' },
  },
  instagram: {
    handle: '@arafahajiumrahgift',
    title: 'Follow the journey',
    url: 'https://www.instagram.com/arafahajiumrahgift/',
    posts: [
      { art: 'giftset', caption: 'Box untuk keluarga Bu Ratna' },
      { art: 'kurma', caption: 'Ajwa baru datang pagi ini' },
      { art: 'sajadah', caption: 'Lipat, masuk tas kabin' },
      { art: 'tasbih', caption: 'Kayu zaitun, 33 butir' },
      { art: 'souvenir', caption: '240 pouch untuk rombongan Solo' },
      { art: 'madu', caption: 'Sidr musim gugur' },
    ],
  },
}

export const values = [
  {
    icon: 'Sparkles',
    title: 'Curated with Care',
    body: 'Kami mencicipi, memegang, dan memakai sendiri semua yang dijual sebelum masuk katalog.',
  },
  {
    icon: 'Gift',
    title: 'Elegant Packaging',
    body: 'Box hardcover, sleeve kertas tebal, dan kartu tulis tangan. Tidak perlu dibungkus ulang.',
  },
  {
    icon: 'BadgeCheck',
    title: 'Quality Products',
    body: 'Kurma disortir manual, madu diuji lab, sajadah ditenun bukan dicetak.',
  },
  {
    icon: 'Send',
    title: 'Ready to Gift',
    body: 'Bisa dikirim langsung ke alamat penerima tanpa nota harga di dalam paket.',
  },
]

export const testimonials = [
  {
    rating: 5,
    quote:
      'Niatnya cuma cari oleh-oleh untuk keluarga, akhirnya semua orang di rumah minta dibawakan lagi.',
    name: 'Ratna Halim',
    city: 'Surabaya',
    context: 'Arafah Premium Box',
  },
  {
    rating: 5,
    quote:
      'Pesan 240 pouch untuk rombongan travel kami. Nama jamaah dicetak satu-satu, sampai tepat waktu, tidak ada yang rusak.',
    name: 'H. Zulkarnain',
    city: 'Solo',
    context: 'Paket Salam × 240',
  },
  {
    rating: 5,
    quote:
      'Dikirim langsung ke rumah ibu saya di kampung dan beliau kira saya yang antar sendiri. Packagingnya rapi sekali.',
    name: 'Dewi Anggraini',
    city: 'Makassar',
    context: 'Family Gift Set',
  },
]

export const faqs = [
  {
    q: 'Apakah bisa pesan dalam jumlah banyak?',
    a: 'Bisa. Mulai 50 pcs harga sudah menyesuaikan jumlah, dan kami siapkan sample terlebih dulu sebelum produksi penuh. Hubungi kami lewat WhatsApp untuk penawaran.',
  },
  {
    q: 'Bisa custom packaging?',
    a: 'Bisa. Kartu ucapan, nama jamaah, logo travel, warna pita, dan sleeve box bisa disesuaikan. Untuk box custom penuh, minimum 100 pcs.',
  },
  {
    q: 'Bisa kirim langsung ke penerima?',
    a: 'Bisa. Isi alamat penerima di halaman checkout, lalu tulis pesan Anda di kolom kartu ucapan. Nota harga tidak pernah kami sertakan di dalam paket.',
  },
  {
    q: 'Berapa lama proses pesanan?',
    a: 'Pesanan satuan dikemas di hari yang sama jika dibayar sebelum pukul 14.00 WIB. Pesanan rombongan butuh 3–5 hari kerja.',
  },
  {
    q: 'Apakah tersedia souvenir rombongan?',
    a: 'Tersedia. Paket Salam adalah pilihan paling sering diambil travel: kurma, tasbih, dan kartu nama jamaah dalam satu pouch.',
  },
  {
    q: 'Bagaimana cara melakukan pembayaran?',
    a: 'Transfer bank (BCA, Mandiri, BSI), QRIS, kartu kredit, dan e-wallet. Untuk pesanan rombongan berlaku DP 50% dan pelunasan sebelum pengiriman.',
  },
]

export const shippingMethods = [
  { id: 'reguler', name: 'Reguler', eta: '2–4 hari', price: 22000 },
  { id: 'kargo', name: 'Kargo rombongan', eta: '4–7 hari', price: 15000 },
  { id: 'sameday', name: 'Same day (Jabodetabek)', eta: 'Hari ini', price: 45000 },
]

export const paymentMethods = [
  { id: 'transfer', name: 'Transfer bank', note: 'BCA, Mandiri, BSI' },
  { id: 'qris', name: 'QRIS', note: 'Semua e-wallet' },
  { id: 'card', name: 'Kartu kredit / debit', note: 'Visa, Mastercard' },
]
