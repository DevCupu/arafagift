// Satu sumber kebenaran untuk tipe blok landing page: dipakai renderer publik
// (shop/LandingPage.vue) dan builder admin (admin/LandingFormPage.vue).
//
// Menambah tipe blok = satu entry di sini + satu entry di
// AdminLandingController::BLOCK_RULES. Tidak ada tempat lain yang perlu diubah.
//
// Tipe field yang dikenali BlockFields.vue:
//   text | textarea | image | icon | products | list (repeater + `fields`)
import BlockBanner from './BlockBanner.vue'
import BlockHero from './BlockHero.vue'
import BlockPopup from './BlockPopup.vue'
import BlockNilai from './BlockNilai.vue'
import BlockProdukUnggulan from './BlockProdukUnggulan.vue'
import BlockTestimoni from './BlockTestimoni.vue'
import BlockFaq from './BlockFaq.vue'
import BlockCta from './BlockCta.vue'

export const ICONS = [
  'Sparkles', 'Gift', 'BadgeCheck', 'Send',
  'Truck', 'Shield', 'Users', 'Heart', 'Check', 'Star', 'Clock', 'Wallet',
]

export const BLOCKS = {
  banner: {
    label: 'Banner / Carousel',
    hint: 'Attention — foto promo bergantian dengan badge & CTA, cocok untuk iklan',
    component: BlockBanner,
    make: () => ({ items: [{ image: '', badge: '', title: '', ctaLabel: '', href: '', alt: '' }] }),
    fields: [
      {
        key: 'items', type: 'list', label: 'Banner', max: 6,
        make: () => ({ image: '', badge: '', title: '', ctaLabel: '', href: '', alt: '' }),
        fields: [
          { key: 'image', type: 'image', label: 'Foto banner', hint: 'Ukuran: 1600×1000px (landscape). Taruh objek/teks penting di tengah — otomatis dipotong lebih kotak di HP, lebih lebar/pipih di desktop.' },
          { key: 'badge', type: 'text', label: 'Badge kecil (opsional)', hint: 'mis. PROMO, DISKON 20%' },
          { key: 'title', type: 'text', label: 'Judul overlay (opsional)', hint: 'Teks besar di atas foto' },
          { key: 'ctaLabel', type: 'text', label: 'Teks tombol (opsional)', hint: 'mis. Pesan Sekarang' },
          { key: 'href', type: 'text', label: 'Link tujuan', hint: 'Kosongkan untuk WhatsApp toko' },
          { key: 'alt', type: 'text', label: 'Teks alternatif (SEO/aksesibilitas)' },
        ],
      },
    ],
  },

  hero: {
    label: 'Hero',
    hint: 'Attention — foto besar, headline, satu CTA',
    component: BlockHero,
    make: () => ({ eyebrow: '', headline: '', sub: '', image: '', cta: { label: '', href: '' } }),
    fields: [
      { key: 'image', type: 'image', label: 'Foto hero', hint: 'Ukuran: minimal 1200×1500px (potret/tegak). Mengisi kolom kanan penuh tinggi, jadi lebih aman potret daripada landscape.' },
      { key: 'eyebrow', type: 'text', label: 'Label kecil', hint: 'mis. Untuk Agen Travel Umrah' },
      { key: 'headline', type: 'textarea', label: 'Headline', hint: 'Enter = baris baru. Baris terakhir tampil emas.' },
      { key: 'sub', type: 'textarea', label: 'Subheadline' },
      { key: 'cta.label', type: 'text', label: 'Teks tombol' },
      { key: 'cta.href', type: 'text', label: 'Link tombol', hint: 'Kosongkan untuk WhatsApp toko' },
    ],
  },

  nilai: {
    label: 'Nilai (hooks)',
    hint: 'Interest — 3-5 pembeda utama, ringkas',
    component: BlockNilai,
    make: () => ({ title: '', items: [{ icon: 'Sparkles', image: '', title: '', body: '' }] }),
    fields: [
      { key: 'title', type: 'text', label: 'Judul section' },
      {
        key: 'items', type: 'list', label: 'Hooks', max: 5,
        make: () => ({ icon: 'Sparkles', image: '', title: '', body: '' }),
        fields: [
          { key: 'image', type: 'image', label: 'Foto banner (opsional)', hint: 'Kalau diisi, foto menggantikan ikon. Ukuran: 800×500px (landscape).' },
          { key: 'icon', type: 'icon', label: 'Ikon', hint: 'Dipakai kalau foto banner kosong' },
          { key: 'title', type: 'text', label: 'Judul pendek' },
          { key: 'body', type: 'textarea', label: 'Satu kalimat' },
        ],
      },
    ],
  },

  produk_unggulan: {
    label: 'Produk Unggulan',
    hint: 'Desire — produk pilihan untuk audiens ini',
    component: BlockProdukUnggulan,
    make: () => ({ eyebrow: '', title: '', intro: '', productIds: [], customItems: [] }),
    fields: [
      { key: 'eyebrow', type: 'text', label: 'Label kecil' },
      { key: 'title', type: 'text', label: 'Judul section' },
      { key: 'intro', type: 'textarea', label: 'Deskripsi singkat', hint: 'Opsional. Satu kalimat pendek di bawah judul.' },
      { key: 'productIds', type: 'products', label: 'Produk dari katalog', hint: 'Urutan centang = urutan tampil' },
      {
        key: 'customItems', type: 'list', label: 'Produk manual (di luar katalog)', max: 8,
        hint: 'Untuk produk yang belum ada di katalog. Tampil setelah produk katalog di atas.',
        make: () => ({
          image: '', name: '', category: '', price: '', comparePrice: '',
          badge: '', rating: '', reviews: '', description: '', includes: [], details: [],
        }),
        fields: [
          { key: 'image', type: 'image', label: 'Foto', hint: 'Ukuran: 1000×1250px (potret 4:5), sama seperti foto produk katalog.' },
          { key: 'name', type: 'text', label: 'Nama produk' },
          { key: 'category', type: 'text', label: 'Kategori', hint: 'mis. Gift Set, Kurma, Sajadah' },
          { key: 'price', type: 'text', label: 'Harga', hint: 'Angka saja, mis. 250000' },
          { key: 'comparePrice', type: 'text', label: 'Harga coret (opsional)', hint: 'Angka saja, mis. 300000' },
          { key: 'badge', type: 'text', label: 'Badge (opsional)', hint: 'mis. Baru, Promo' },
          { key: 'rating', type: 'text', label: 'Rating (opsional)', hint: 'mis. 4.9' },
          { key: 'reviews', type: 'text', label: 'Jumlah review (opsional)', hint: 'mis. 214' },
          { key: 'description', type: 'textarea', label: 'Detail singkat', hint: 'Muncul di modal detail.' },
          {
            key: 'includes', type: 'list', label: 'Isi paket', max: 6,
            make: () => ({ value: '' }),
            fields: [
              { key: 'value', type: 'text', label: 'Item' },
            ],
          },
          {
            key: 'details', type: 'list', label: 'Spesifikasi', max: 6,
            make: () => ({ label: '', value: '' }),
            fields: [
              { key: 'label', type: 'text', label: 'Label' },
              { key: 'value', type: 'text', label: 'Nilai' },
            ],
          },
        ],
      },
    ],
  },

  testimoni: {
    label: 'Testimoni',
    hint: 'Desire — bukti sosial dari audiens sejenis',
    component: BlockTestimoni,
    make: () => ({ title: '', items: [{ name: '', role: '', quote: '', avatar: '' }] }),
    fields: [
      { key: 'title', type: 'text', label: 'Judul section' },
      {
        key: 'items', type: 'list', label: 'Testimoni', max: 6,
        make: () => ({ name: '', role: '', quote: '', avatar: '' }),
        fields: [
          { key: 'avatar', type: 'image', label: 'Foto (opsional)', hint: 'Ukuran: minimal 200×200px, persegi, wajah di tengah — tampil sebagai foto bulat kecil.' },
          { key: 'name', type: 'text', label: 'Nama' },
          { key: 'role', type: 'text', label: 'Instansi / jabatan' },
          { key: 'quote', type: 'textarea', label: 'Kutipan' },
        ],
      },
    ],
  },

  faq: {
    label: 'FAQ',
    hint: 'Desire — hapus keraguan terakhir',
    component: BlockFaq,
    make: () => ({ title: '', items: [{ q: '', a: '' }] }),
    fields: [
      { key: 'title', type: 'text', label: 'Judul section' },
      {
        key: 'items', type: 'list', label: 'Pertanyaan', max: 8,
        make: () => ({ q: '', a: '' }),
        fields: [
          { key: 'q', type: 'text', label: 'Pertanyaan' },
          { key: 'a', type: 'textarea', label: 'Jawaban' },
        ],
      },
    ],
  },

  cta: {
    label: 'CTA Penutup',
    hint: 'Action — ajakan terakhir',
    component: BlockCta,
    make: () => ({ headline: '', sub: '', note: '', cta: { label: '', href: '' } }),
    fields: [
      { key: 'headline', type: 'text', label: 'Headline' },
      { key: 'sub', type: 'textarea', label: 'Subheadline' },
      { key: 'cta.label', type: 'text', label: 'Teks tombol' },
      { key: 'cta.href', type: 'text', label: 'Link tombol', hint: 'Kosongkan untuk WhatsApp toko' },
      { key: 'note', type: 'text', label: 'Catatan kecil', hint: 'mis. Dibalas < 1 jam' },
    ],
  },

  popup: {
    label: 'Popup Promo',
    hint: 'Attention — muncul otomatis di atas layar setelah beberapa detik',
    component: BlockPopup,
    make: () => ({ image: '', badge: '', title: '', sub: '', ctaLabel: 'Chat Sekarang', href: '', delaySeconds: '4' }),
    fields: [
      { key: 'image', type: 'image', label: 'Foto (opsional)', hint: 'Ukuran: minimal 300×300px, persegi — tampil kecil sebagai thumbnail di pojok popup.' },
      { key: 'badge', type: 'text', label: 'Badge kecil (opsional)', hint: 'mis. PROMO TERBATAS' },
      { key: 'title', type: 'text', label: 'Judul' },
      { key: 'sub', type: 'textarea', label: 'Deskripsi singkat (opsional)' },
      { key: 'ctaLabel', type: 'text', label: 'Teks tombol' },
      { key: 'href', type: 'text', label: 'Link tujuan', hint: 'Kosongkan untuk WhatsApp toko' },
      { key: 'delaySeconds', type: 'text', label: 'Muncul setelah (detik)', hint: 'mis. 4. Cuma tampil sekali per kunjungan.' },
    ],
  },
}

export const PRESETS = {
  'Iklan Singkat': ['hero', 'nilai', 'produk_unggulan', 'cta'],
  'Landing Lengkap': ['hero', 'nilai', 'produk_unggulan', 'testimoni', 'faq', 'cta'],
}

export const newBlock = (type) => ({ type, enabled: true, content: BLOCKS[type].make() })

// Baca/tulis lewat key bertitik ("cta.label") supaya skema field tetap datar.
export const getPath = (obj, path) => path.split('.').reduce((o, k) => o?.[k], obj)

export const setPath = (obj, path, value) => {
  const keys = path.split('.')
  const last = keys.pop()
  const target = keys.reduce((o, k) => (o[k] ??= {}), obj)
  target[last] = value
}
