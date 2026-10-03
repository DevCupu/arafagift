<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import {
  AlertTriangle,
  BadgeCheck,
  ChevronRight,
  CreditCard,
  Database,
  Globe2,
  Info,
  Layers,
  MessageSquare,
  Power,
  RefreshCw,
  Save,
  Server,
  Shield,
  SlidersHorizontal,
  Store,
  Trash2,
  Truck,
  Zap,
} from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import DestinationSearch from '@/components/shop/DestinationSearch.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  settings: { type: Object, required: true },
  systemInfo: { type: Object, default: () => ({}) },
})
const { push } = useToast()

const activeTab = ref('profile')
const cacheLoading = ref(null)

const tabs = [
  { id: 'profile',     label: 'Profil & Identitas',     icon: Store,         desc: 'Nama, tagline, alamat & jam buka' },
  { id: 'contact',     label: 'Kontak & Media Sosial',   icon: MessageSquare, desc: 'WhatsApp, email & akun medsos' },
  { id: 'shipping',    label: 'Penjualan & Logistik',    icon: Truck,         desc: 'Gratis ongkir, kota & handling time' },
  { id: 'operation',   label: 'Status & Operasional',    icon: Power,         desc: 'Status toko, ambang stok & pesan tutup' },
  { id: 'payment',     label: 'Rekening & Pembayaran',   icon: CreditCard,    desc: 'Bank, nomor rekening & instruksi' },
  { id: 'seo',         label: 'SEO & Analitik',          icon: Globe2,        desc: 'Meta tags, GA4 & Meta Pixel' },
  { id: 'maintenance', label: 'Sistem & Pemeliharaan',   icon: Server,        desc: 'Cache, OPcache, maintenance & info server' },
]

const form = useForm({
  store_name:                props.settings.store_name ?? 'Arafagift',
  tagline:                   props.settings.tagline ?? '',
  address:                   props.settings.address ?? '',
  business_hours:            props.settings.business_hours ?? '',
  origin_city:               props.settings.origin_city ?? 'Makassar',
  origin_destination_id:     props.settings.origin_destination_id ?? null,
  email:                     props.settings.email ?? '',
  whatsapp:                  props.settings.whatsapp ?? '',
  whatsapp_secondary:        props.settings.whatsapp_secondary ?? '',
  instagram_url:             props.settings.instagram_url ?? '',
  tiktok_url:                props.settings.tiktok_url ?? '',
  maps_url:                  props.settings.maps_url ?? '',
  free_shipping_from:        props.settings.free_shipping_from ?? 0,
  free_shipping_cities:      props.settings.free_shipping_cities ?? '',
  bulk_minimum:              props.settings.bulk_minimum ?? 0,
  handling_time:             props.settings.handling_time ?? '',
  shipping_note:             props.settings.shipping_note ?? '',
  store_status:              props.settings.store_status ?? 'open',
  closed_message:            props.settings.closed_message ?? '',
  low_stock_threshold:       props.settings.low_stock_threshold ?? 5,
  maintenance_title:         props.settings.maintenance_title ?? '',
  maintenance_end_time:      props.settings.maintenance_end_time ?? '',
  maintenance_whitelist_ips: props.settings.maintenance_whitelist_ips ?? '',
  maintenance_secret:        props.settings.maintenance_secret ?? '',
  bank_name:                 props.settings.bank_name ?? '',
  bank_account_number:       props.settings.bank_account_number ?? '',
  bank_account_name:         props.settings.bank_account_name ?? '',
  payment_instructions:      props.settings.payment_instructions ?? '',
  meta_title:                props.settings.meta_title ?? '',
  meta_description:          props.settings.meta_description ?? '',
  google_analytics_id:       props.settings.google_analytics_id ?? '',
  facebook_pixel_id:         props.settings.facebook_pixel_id ?? '',
})

const save = () => {
  form.put('/admin/pengaturan', {
    preserveScroll: true,
    onSuccess: () => push('Pengaturan sistem berhasil diperbarui ✓', { tone: 'success' }),
  })
}

const cacheAction = (action, label) => {
  cacheLoading.value = action
  router.post(`/admin/pengaturan/cache/${action}`, {}, {
    preserveScroll: true,
    onSuccess: () => push(label + ' berhasil.', { tone: 'success' }),
    onError: () => push('Gagal: ' + label + '.', { tone: 'danger' }),
    onFinish: () => { cacheLoading.value = null },
  })
}

const statusBadge = computed(() => {
  const map = {
    open:        { label: 'Buka Normal', cls: 'bg-emerald-500/15 text-emerald-700 ring-1 ring-emerald-500/30' },
    holiday:     { label: 'Libur Musiman', cls: 'bg-amber-500/15 text-amber-700 ring-1 ring-amber-500/30' },
    maintenance: { label: 'Tutup Sementara', cls: 'bg-rose-500/15 text-rose-700 ring-1 ring-rose-500/30' },
  }
  return map[form.store_status] ?? map['open']
})

const formatRp = (n) => new Intl.NumberFormat('id-ID').format(n || 0)
</script>

<template>
  <div class="space-y-6">
    <!-- ═══════════════════════════ HEADER ═══════════════════════════ -->
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-line pb-6">
      <div>
        <div class="flex items-center gap-2">
          <span class="grid h-7 w-7 place-items-center rounded-lg bg-gold/15 text-gold">
            <SlidersHorizontal class="h-4 w-4" />
          </span>
          <span class="text-xs font-semibold tracking-wider text-muted uppercase">Konfigurasi Sistem</span>
        </div>
        <h1 class="mt-1 font-display text-3xl text-forest">Pengaturan Sistem</h1>
        <p class="mt-1 text-sm text-muted">
          Pusat kendali identitas, logistik, pembayaran, SEO, status toko, dan manajemen server.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <!-- status badge realtime -->
        <span :class="['inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium', statusBadge.cls]">
          <span class="h-1.5 w-1.5 rounded-full bg-current" />
          {{ statusBadge.label }}
        </span>
        <AppButton size="sm" :loading="form.processing" @click="save">
          <template #icon><Save class="h-4 w-4" /></template>
          Simpan Semua
        </AppButton>
      </div>
    </header>

    <!-- Error Alert Banner -->
    <div v-if="Object.keys(form.errors).length" class="rounded-xl border border-danger/40 bg-danger/10 p-5 text-sm text-danger">
      <p class="font-semibold flex items-center gap-2">
        <AlertTriangle class="h-4 w-4" />
        <span>Gagal menyimpan — terdapat isian yang belum sesuai:</span>
      </p>
      <ul class="mt-2.5 list-disc space-y-1 pl-5 text-xs">
        <li v-for="(msg, key) in form.errors" :key="key">{{ Array.isArray(msg) ? msg[0] : msg }}</li>
      </ul>
    </div>

    <!-- ═══════════════════════════ GRID LAYOUT ═══════════════════════════ -->
    <div class="grid gap-6 lg:grid-cols-12 items-start">

      <!-- Sidebar Tabs -->
      <nav class="lg:col-span-4 space-y-1 rounded-xl border border-line bg-surface p-2 shadow-soft">
        <button
          v-for="t in tabs"
          :key="t.id"
          type="button"
          class="flex w-full items-start gap-3 rounded-lg p-3 text-left transition-all duration-200"
          :class="activeTab === t.id
            ? 'bg-gold/15 text-forest font-medium border-l-4 border-gold shadow-sm'
            : 'text-muted hover:bg-ivory/60 hover:text-forest'"
          @click="activeTab = t.id"
        >
          <component
            :is="t.icon"
            class="mt-0.5 h-4 w-4 flex-none transition"
            :class="activeTab === t.id ? 'text-gold' : 'text-muted/70'"
          />
          <div class="min-w-0 flex-1">
            <p class="text-sm leading-tight">{{ t.label }}</p>
            <p class="mt-0.5 text-[0.72rem] text-muted line-clamp-1">{{ t.desc }}</p>
          </div>
          <ChevronRight class="mt-0.5 h-3.5 w-3.5 flex-none text-muted/40 transition" :class="activeTab === t.id ? 'opacity-100' : 'opacity-0'" />
        </button>
      </nav>

      <!-- Panel Content -->
      <main class="lg:col-span-8 space-y-6">

        <!-- ════════════ TAB 1: PROFIL & IDENTITAS ════════════ -->
        <section v-if="activeTab === 'profile'" class="rounded-xl border border-line bg-surface shadow-soft">
          <div class="border-b border-line px-6 py-5">
            <h2 class="font-display text-xl text-forest flex items-center gap-2">
              <Store class="h-5 w-5 text-gold" />Profil & Identitas Toko
            </h2>
            <p class="text-xs text-muted mt-0.5">Informasi brand utama yang tampil di header, invoice, footer, dan structured data SEO.</p>
          </div>
          <div class="p-6 sm:p-7 grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <label class="field-label" for="s-name">Nama Toko <span class="text-danger">*</span></label>
              <input id="s-name" v-model="form.store_name" class="field" placeholder="Arafagift" />
              <p v-if="form.errors.store_name" class="mt-1 text-xs text-danger">{{ form.errors.store_name }}</p>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label" for="s-tagline">Tagline / Slogan Toko</label>
              <input id="s-tagline" v-model="form.tagline" class="field" placeholder="Oleh-oleh Haji & Umrah yang dipilih dengan hati." />
              <p class="mt-1 text-xs text-muted">Ditampilkan di footer, hero section, dan meta description sekunder.</p>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label" for="s-address">Alamat Fisik / Toko Offline</label>
              <textarea id="s-address" v-model="form.address" rows="3" class="field" placeholder="Jl. Abdullah Daeng Sirua No. 61, Kota Makassar, Sulawesi Selatan 90222" />
              <p class="mt-1 text-xs text-muted">Digunakan untuk pickup logistik, footer, dan data terstruktur Schema.org (LocalBusiness).</p>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label" for="s-hours">Jam Operasional</label>
              <input id="s-hours" v-model="form.business_hours" class="field" placeholder="Senin – Sabtu: 08.00 – 17.00 WITA (Minggu Libur)" />
              <p class="mt-1 text-xs text-muted">Jadwal layanan pelanggan dan operasional gerai yang tampil di halaman Kontak & footer.</p>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label" for="s-origin">Kota Asal Pengiriman (Ekspedisi)</label>
              <DestinationSearch
                id="s-origin"
                v-model="form.origin_destination_id"
                :initial-label="form.origin_city"
                @select="(d) => { form.origin_city = d ? d.label : '' }"
              />
              <p class="mt-1.5 text-xs text-muted">Kota asal paket untuk perhitungan tarif ongkos kirim otomatis via API Raja Ongkir.</p>
            </div>
          </div>
        </section>

        <!-- ════════════ TAB 2: KONTAK & SOSIAL MEDIA ════════════ -->
        <section v-if="activeTab === 'contact'" class="rounded-xl border border-line bg-surface shadow-soft">
          <div class="border-b border-line px-6 py-5">
            <h2 class="font-display text-xl text-forest flex items-center gap-2">
              <MessageSquare class="h-5 w-5 text-gold" />Kontak & Media Sosial
            </h2>
            <p class="text-xs text-muted mt-0.5">Nomor WhatsApp, email transaksi, dan tautan platform sosial media resmi toko.</p>
          </div>
          <div class="p-6 sm:p-7 grid gap-5 sm:grid-cols-2">
            <div>
              <label class="field-label" for="s-wa">WhatsApp Utama (Pemesanan) <span class="text-danger">*</span></label>
              <input id="s-wa" v-model="form.whatsapp" class="field" placeholder="08192242444" />
              <p class="mt-1 text-xs text-muted">Nomor tujuan notifikasi pesanan otomatis saat pelanggan checkout.</p>
              <p v-if="form.errors.whatsapp" class="mt-1 text-xs text-danger">{{ form.errors.whatsapp }}</p>
            </div>

            <div>
              <label class="field-label" for="s-wa-sec">WhatsApp Cadangan / CS 2</label>
              <input id="s-wa-sec" v-model="form.whatsapp_secondary" class="field" placeholder="081234567890" />
              <p class="mt-1 text-xs text-muted">Kontak alternatif jika CS utama sedang berhalangan atau tidak aktif.</p>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label" for="s-email">Email Resmi Toko</label>
              <input id="s-email" v-model="form.email" type="email" class="field" placeholder="halo@arafagift.id" />
              <p class="mt-1 text-xs text-muted">Digunakan untuk korespondensi bisnis, notifikasi transaksi, dan pengiriman invoice.</p>
              <p v-if="form.errors.email" class="mt-1 text-xs text-danger">{{ form.errors.email }}</p>
            </div>

            <div>
              <label class="field-label" for="s-ig">Instagram</label>
              <input id="s-ig" v-model="form.instagram_url" class="field" placeholder="https://instagram.com/arafagift.id" />
              <p class="mt-1 text-xs text-muted">Url lengkap profil Instagram resmi.</p>
            </div>

            <div>
              <label class="field-label" for="s-tiktok">TikTok</label>
              <input id="s-tiktok" v-model="form.tiktok_url" class="field" placeholder="https://tiktok.com/@arafagift" />
              <p class="mt-1 text-xs text-muted">URL lengkap akun TikTok resmi.</p>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label" for="s-maps">Tautan Google Maps Lokasi</label>
              <input id="s-maps" v-model="form.maps_url" class="field" placeholder="https://maps.app.goo.gl/..." />
              <p class="mt-1 text-xs text-muted">Tombol "Lihat di Peta" pada footer dan halaman Kontak akan mengarah ke URL ini.</p>
            </div>
          </div>
        </section>

        <!-- ════════════ TAB 3: PENJUALAN & LOGISTIK ════════════ -->
        <section v-if="activeTab === 'shipping'" class="rounded-xl border border-line bg-surface shadow-soft">
          <div class="border-b border-line px-6 py-5">
            <h2 class="font-display text-xl text-forest flex items-center gap-2">
              <Truck class="h-5 w-5 text-gold" />Penjualan & Ketentuan Logistik
            </h2>
            <p class="text-xs text-muted mt-0.5">Aturan gratis ongkir, minimum order grosir, durasi pengemasan, dan catatan pengiriman.</p>
          </div>
          <div class="p-6 sm:p-7 grid gap-5 sm:grid-cols-2">
            <div>
              <label class="field-label" for="s-free">Minimal Belanja Gratis Ongkir (Rp)</label>
              <input id="s-free" v-model="form.free_shipping_from" type="number" min="0" class="field" placeholder="750000" />
              <p class="mt-1 text-xs text-muted">Total belanja agar otomatis gratis ongkir ke seluruh wilayah. Isi <strong>0</strong> untuk menonaktifkan.</p>
            </div>

            <div>
              <label class="field-label" for="s-bulk">Minimum Qty Order Rombongan (Pcs)</label>
              <input id="s-bulk" v-model="form.bulk_minimum" type="number" min="0" class="field" placeholder="50" />
              <p class="mt-1 text-xs text-muted">Kuantitas minimum agar pembeli mendapatkan harga paket souvenir rombongan.</p>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label" for="s-cities">Kota Bebas Ongkir Tanpa Syarat Nominal</label>
              <input id="s-cities" v-model="form.free_shipping_cities" class="field" placeholder="Makassar, Maros, Gowa, Sungguminasa" />
              <p class="mt-1 text-xs text-muted">Pisahkan dengan koma. Pembeli di kota ini selalu gratis ongkir tanpa batas minimum belanja.</p>
            </div>

            <div>
              <label class="field-label" for="s-handling">Waktu Pengemasan (Handling Time)</label>
              <input id="s-handling" v-model="form.handling_time" class="field" placeholder="1 – 2 hari kerja" />
              <p class="mt-1 text-xs text-muted">Estimasi durasi packing sebelum paket diserahkan ke kurir ekspedisi.</p>
            </div>

            <div>
              <label class="field-label" for="s-shipnote">Catatan Pengiriman (Tampil di Checkout)</label>
              <input id="s-shipnote" v-model="form.shipping_note" class="field" placeholder="Order sebelum 15:00 WITA dikirim hari yang sama" />
              <p class="mt-1 text-xs text-muted">Pesan panduan pengiriman yang tampil pada rincian checkout pelanggan.</p>
            </div>

            <!-- Summary card -->
            <div class="sm:col-span-2 rounded-lg bg-ivory border border-line p-4">
              <p class="text-xs font-semibold text-forest mb-2 flex items-center gap-1.5">
                <Info class="h-3.5 w-3.5 text-gold" />Ringkasan Konfigurasi Logistik
              </p>
              <div class="grid grid-cols-2 gap-2 text-xs text-muted">
                <div>Gratis ongkir min: <strong class="text-forest">Rp {{ formatRp(form.free_shipping_from) }}</strong></div>
                <div>Min. rombongan: <strong class="text-forest">{{ form.bulk_minimum || '-' }} pcs</strong></div>
                <div>Handling: <strong class="text-forest">{{ form.handling_time || '-' }}</strong></div>
                <div>Kota bebas: <strong class="text-forest">{{ form.free_shipping_cities || '-' }}</strong></div>
              </div>
            </div>
          </div>
        </section>

        <!-- ════════════ TAB 4: STATUS & OPERASIONAL ════════════ -->
        <section v-if="activeTab === 'operation'" class="rounded-xl border border-line bg-surface shadow-soft">
          <div class="border-b border-line px-6 py-5">
            <h2 class="font-display text-xl text-forest flex items-center gap-2">
              <Power class="h-5 w-5 text-gold" />Status Toko & Operasional
            </h2>
            <p class="text-xs text-muted mt-0.5">Kontrol penerimaan pesanan, pesan pengumuman, dan ambang peringatan stok menipis.</p>
          </div>
          <div class="p-6 sm:p-7 space-y-6">

            <!-- Status selector dengan card visual -->
            <div>
              <label class="field-label mb-3 block">Status Penerimaan Pesanan</label>
              <div class="grid gap-3 sm:grid-cols-3">
                <label
                  v-for="opt in [
                    { value: 'open', emoji: '🟢', label: 'Buka Normal', desc: 'Menerima pesanan secara normal' },
                    { value: 'holiday', emoji: '🟡', label: 'Libur / Hari Raya', desc: 'Pesanan masuk dipending sementara' },
                    { value: 'maintenance', emoji: '🔴', label: 'Tutup Sementara', desc: 'Halaman toko menampilkan banner tutup' },
                  ]"
                  :key="opt.value"
                  :for="`opt-${opt.value}`"
                  class="relative flex cursor-pointer flex-col gap-1.5 rounded-xl border p-4 transition-all"
                  :class="form.store_status === opt.value
                    ? 'border-gold bg-gold/10 shadow-sm ring-1 ring-gold/40'
                    : 'border-line bg-ivory/40 hover:border-gold/40 hover:bg-ivory/80'"
                >
                  <input :id="`opt-${opt.value}`" v-model="form.store_status" type="radio" :value="opt.value" class="sr-only" />
                  <span class="text-xl">{{ opt.emoji }}</span>
                  <span class="text-sm font-semibold text-forest">{{ opt.label }}</span>
                  <span class="text-xs text-muted leading-relaxed">{{ opt.desc }}</span>
                  <BadgeCheck v-if="form.store_status === opt.value" class="absolute right-3 top-3 h-4 w-4 text-gold" />
                </label>
              </div>
            </div>

            <!-- Pesan tutup (muncul jika bukan open) -->
            <div v-if="form.store_status !== 'open'" class="rounded-xl border border-amber-400/40 bg-amber-50/60 p-5 space-y-4">
              <p class="text-xs font-semibold text-amber-700 flex items-center gap-1.5">
                <AlertTriangle class="h-3.5 w-3.5" />
                Pengaturan Banner Pengumuman Tutup / Libur
              </p>
              <div>
                <label class="field-label" for="s-closed-msg">Pesan Pengumuman</label>
                <textarea
                  id="s-closed-msg"
                  v-model="form.closed_message"
                  rows="3"
                  class="field"
                  placeholder="Toko kami sedang libur Hari Raya. Pesanan yang masuk akan diproses mulai tanggal 5 April 2026. Terima kasih atas pengertiannya!"
                />
                <p class="mt-1 text-xs text-muted">Pesan ini ditampilkan sebagai banner pengumuman di seluruh halaman toko untuk pengunjung.</p>
              </div>
              <div>
                <label class="field-label" for="s-maint-title">Judul Banner (opsional)</label>
                <input id="s-maint-title" v-model="form.maintenance_title" class="field" placeholder="Toko Sedang Libur Lebaran" />
              </div>
              <div>
                <label class="field-label" for="s-maint-end">Estimasi Kembali Buka</label>
                <input id="s-maint-end" v-model="form.maintenance_end_time" class="field" placeholder="Senin, 5 April 2026 – 08.00 WITA" />
                <p class="mt-1 text-xs text-muted">Informasi waktu buka kembali yang tampil di banner pengumuman.</p>
              </div>
            </div>

            <!-- Whitelist IP maintenance -->
            <div v-if="form.store_status !== 'open'" class="rounded-xl border border-line bg-ivory/40 p-5 space-y-4">
              <p class="text-xs font-semibold text-forest flex items-center gap-1.5">
                <Shield class="h-3.5 w-3.5 text-gold" />Akses Bypass Maintenance (IP Whitelist)
              </p>
              <div>
                <label class="field-label" for="s-whitelist">IP Address yang Diizinkan Masuk</label>
                <textarea
                  id="s-whitelist"
                  v-model="form.maintenance_whitelist_ips"
                  rows="2"
                  class="field font-mono text-xs"
                  placeholder="192.168.1.1, 103.47.xx.xx"
                />
                <p class="mt-1 text-xs text-muted">Pisahkan dengan koma. Admin dengan IP ini tetap dapat mengakses storefront saat maintenance.</p>
              </div>
              <div>
                <label class="field-label" for="s-secret">Secret Key Bypass (via URL ?bypass=KEY)</label>
                <input id="s-secret" v-model="form.maintenance_secret" class="field font-mono text-xs" placeholder="rahasia-internal-2026" />
                <p class="mt-1 text-xs text-muted">Akses ke storefront dengan menambahkan <code class="bg-ivory px-1 rounded">?bypass=KEY</code> pada URL.</p>
              </div>
            </div>

            <!-- Low stock threshold -->
            <div class="pt-2 border-t border-line">
              <label class="field-label" for="s-lowstock">Ambang Peringatan Stok Menipis (Default)</label>
              <div class="flex items-center gap-3">
                <div class="max-w-[180px]">
                  <input id="s-lowstock" v-model="form.low_stock_threshold" type="number" min="1" max="1000" class="field text-center" placeholder="5" />
                </div>
                <span class="text-xs text-muted">unit/pcs</span>
              </div>
              <p class="mt-1 text-xs text-muted">Produk dengan stok ≤ angka ini akan ditandai badge peringatan kuning di tabel inventori admin.</p>
            </div>
          </div>
        </section>

        <!-- ════════════ TAB 5: REKENING PEMBAYARAN ════════════ -->
        <section v-if="activeTab === 'payment'" class="rounded-xl border border-line bg-surface shadow-soft">
          <div class="border-b border-line px-6 py-5">
            <h2 class="font-display text-xl text-forest flex items-center gap-2">
              <CreditCard class="h-5 w-5 text-gold" />Rekening Pembayaran & Transfer Manual
            </h2>
            <p class="text-xs text-muted mt-0.5">Informasi rekening bank tujuan transfer yang disertakan pada halaman konfirmasi dan pesanan.</p>
          </div>
          <div class="p-6 sm:p-7 grid gap-5 sm:grid-cols-2">
            <div>
              <label class="field-label" for="s-bank">Nama Bank</label>
              <input id="s-bank" v-model="form.bank_name" class="field" placeholder="Bank Syariah Indonesia (BSI)" />
              <p class="mt-1 text-xs text-muted">Nama bank lengkap sesuai rekening penerima.</p>
            </div>

            <div>
              <label class="field-label" for="s-acc-no">Nomor Rekening</label>
              <input id="s-acc-no" v-model="form.bank_account_number" class="field font-mono tracking-widest" placeholder="7123456789" />
              <p class="mt-1 text-xs text-muted">Nomor rekening persis seperti tertera di buku tabungan / ATM.</p>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label" for="s-acc-name">Atas Nama Pemilik Rekening</label>
              <input id="s-acc-name" v-model="form.bank_account_name" class="field" placeholder="PT Arafagift Indonesia / Nama Pemilik" />
              <p class="mt-1 text-xs text-muted">Harus sesuai persis dengan nama pemilik rekening agar pelanggan dapat memverifikasi keaslian transfer.</p>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label" for="s-pay-ins">Petunjuk / Instruksi Pembayaran</label>
              <textarea
                id="s-pay-ins"
                v-model="form.payment_instructions"
                rows="4"
                class="field"
                placeholder="Harap sertakan nomor pesanan pada berita transfer. Bukti pembayaran dikirim ke WhatsApp resmi kami dalam 1×24 jam setelah transfer."
              />
              <p class="mt-1 text-xs text-muted">Panduan bayar yang tampil di halaman konfirmasi pesanan setelah checkout berhasil.</p>
            </div>

            <!-- Preview card rekening -->
            <div class="sm:col-span-2">
              <p class="text-xs font-semibold text-forest mb-2 flex items-center gap-1.5">
                <Info class="h-3.5 w-3.5 text-gold" />Preview Tampilan Rekening Pembeli
              </p>
              <div class="rounded-xl border border-gold/30 bg-gradient-to-br from-amber-50 to-yellow-50 p-5">
                <p class="text-xs text-muted mb-1">Transfer langsung ke:</p>
                <p class="font-display text-lg text-forest font-bold">{{ form.bank_name || 'Nama Bank' }}</p>
                <p class="mt-1 font-mono text-2xl font-bold tracking-widest text-forest/80">{{ form.bank_account_number || '– – – –' }}</p>
                <p class="mt-1 text-sm text-muted">a/n <strong class="text-forest">{{ form.bank_account_name || 'Nama Pemilik' }}</strong></p>
              </div>
            </div>
          </div>
        </section>

        <!-- ════════════ TAB 6: SEO & ANALITIK ════════════ -->
        <section v-if="activeTab === 'seo'" class="rounded-xl border border-line bg-surface shadow-soft">
          <div class="border-b border-line px-6 py-5">
            <h2 class="font-display text-xl text-forest flex items-center gap-2">
              <Globe2 class="h-5 w-5 text-gold" />SEO & Pelacakan Web (Analytics)
            </h2>
            <p class="text-xs text-muted mt-0.5">Meta tag global toko dan integrasi kode pelacakan Google Analytics 4 & Meta Pixel.</p>
          </div>
          <div class="p-6 sm:p-7 grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <label class="field-label" for="s-meta-title">Default Meta Title</label>
              <input id="s-meta-title" v-model="form.meta_title" class="field" placeholder="Arafagift — Oleh-oleh Haji & Umrah Premium Makassar" />
              <div class="flex items-center justify-between mt-1">
                <p class="text-xs text-muted">Judul yang tampil di tab browser dan hasil pencarian Google (ideal 50–70 karakter).</p>
                <span class="text-xs" :class="(form.meta_title?.length ?? 0) > 70 ? 'text-danger' : 'text-muted'">
                  {{ form.meta_title?.length ?? 0 }}/70
                </span>
              </div>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label" for="s-meta-desc">Default Meta Description</label>
              <textarea
                id="s-meta-desc"
                v-model="form.meta_description"
                rows="3"
                class="field"
                placeholder="Pusat oleh-oleh haji dan umrah terpercaya di Makassar. Menyediakan kurma ajwa, sajadah premium, air zam-zam asli, dan souvenir rombongan."
              />
              <div class="flex items-center justify-between mt-1">
                <p class="text-xs text-muted">Ringkasan toko di hasil penelusuran search engine (ideal 120–160 karakter).</p>
                <span class="text-xs" :class="(form.meta_description?.length ?? 0) > 160 ? 'text-danger' : 'text-muted'">
                  {{ form.meta_description?.length ?? 0 }}/160
                </span>
              </div>
            </div>

            <div>
              <label class="field-label" for="s-ga">Google Analytics 4 — Measurement ID</label>
              <input id="s-ga" v-model="form.google_analytics_id" class="field font-mono" placeholder="G-XXXXXXXXXX" />
              <p class="mt-1 text-xs text-muted">ID properti GA4 dari Google Analytics. Kosongkan untuk menonaktifkan.</p>
              <p v-if="form.errors.google_analytics_id" class="mt-1 text-xs text-danger">{{ form.errors.google_analytics_id }}</p>
            </div>

            <div>
              <label class="field-label" for="s-pixel">Meta / Facebook Pixel ID</label>
              <input id="s-pixel" v-model="form.facebook_pixel_id" class="field font-mono" placeholder="123456789012345" />
              <p class="mt-1 text-xs text-muted">ID pelacakan konversi iklan Meta Ads (Facebook & Instagram). Kosongkan untuk menonaktifkan.</p>
              <p v-if="form.errors.facebook_pixel_id" class="mt-1 text-xs text-danger">{{ form.errors.facebook_pixel_id }}</p>
            </div>

            <!-- SEO preview mock -->
            <div class="sm:col-span-2 rounded-xl border border-line bg-white p-5 space-y-1">
              <p class="text-[0.65rem] text-muted mb-2 font-mono uppercase tracking-wider">Preview Google Search Result</p>
              <p class="text-blue-600 text-sm font-medium leading-snug line-clamp-1">{{ form.meta_title || 'Arafagift — Oleh-oleh Haji & Umrah Premium' }}</p>
              <p class="text-[0.7rem] text-green-700">arafagift.id › produk</p>
              <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed">{{ form.meta_description || 'Deskripsi toko belum diisi.' }}</p>
            </div>
          </div>
        </section>

        <!-- ════════════ TAB 7: SISTEM & PEMELIHARAAN ════════════ -->
        <section v-if="activeTab === 'maintenance'" class="space-y-5">

          <!-- Cache Management -->
          <div class="rounded-xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-6 py-5">
              <h2 class="font-display text-xl text-forest flex items-center gap-2">
                <Database class="h-5 w-5 text-gold" />Manajemen Cache Aplikasi
              </h2>
              <p class="text-xs text-muted mt-0.5">Kelola cache payload, view template, dan OPcache PHP tanpa perlu akses SSH ke server.</p>
            </div>
            <div class="p-6 sm:p-7 grid gap-4 sm:grid-cols-2">

              <div class="rounded-xl border border-line bg-ivory/60 p-5 space-y-3">
                <div class="flex items-start gap-3">
                  <span class="grid h-9 w-9 flex-none place-items-center rounded-lg bg-rose-500/15 text-rose-600">
                    <Trash2 class="h-4 w-4" />
                  </span>
                  <div>
                    <p class="text-sm font-semibold text-forest">Flush Semua Cache</p>
                    <p class="text-xs text-muted leading-relaxed mt-0.5">Hapus seluruh data cache aplikasi (homepage, koleksi, produk, sitemap, pengaturan). Berguna setelah update data massal.</p>
                  </div>
                </div>
                <button
                  type="button"
                  :disabled="cacheLoading !== null"
                  class="w-full rounded-lg border border-rose-400/40 bg-rose-50 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                  @click="cacheAction('clear-all', 'Flush cache')"
                >
                  <RefreshCw class="h-3.5 w-3.5" :class="cacheLoading === 'clear-all' ? 'animate-spin' : ''" />
                  {{ cacheLoading === 'clear-all' ? 'Memproses…' : 'Flush Semua Cache' }}
                </button>
              </div>

              <div class="rounded-xl border border-line bg-ivory/60 p-5 space-y-3">
                <div class="flex items-start gap-3">
                  <span class="grid h-9 w-9 flex-none place-items-center rounded-lg bg-emerald-500/15 text-emerald-600">
                    <Zap class="h-4 w-4" />
                  </span>
                  <div>
                    <p class="text-sm font-semibold text-forest">Rebuild Cache (Rewarm)</p>
                    <p class="text-xs text-muted leading-relaxed mt-0.5">Bangun ulang seluruh cache payload toko (homepage, koleksi, sitemap, pengaturan) agar performa respon selalu cepat.</p>
                  </div>
                </div>
                <button
                  type="button"
                  :disabled="cacheLoading !== null"
                  class="w-full rounded-lg border border-emerald-400/40 bg-emerald-50 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                  @click="cacheAction('rewarm', 'Rebuild cache')"
                >
                  <RefreshCw class="h-3.5 w-3.5" :class="cacheLoading === 'rewarm' ? 'animate-spin' : ''" />
                  {{ cacheLoading === 'rewarm' ? 'Membangun…' : 'Rebuild Cache Sekarang' }}
                </button>
              </div>

              <div class="rounded-xl border border-line bg-ivory/60 p-5 space-y-3">
                <div class="flex items-start gap-3">
                  <span class="grid h-9 w-9 flex-none place-items-center rounded-lg bg-blue-500/15 text-blue-600">
                    <Layers class="h-4 w-4" />
                  </span>
                  <div>
                    <p class="text-sm font-semibold text-forest">Clear View Templates</p>
                    <p class="text-xs text-muted leading-relaxed mt-0.5">Hapus kompilasi template Blade yang ter-cache. Gunakan jika ada perubahan layout yang belum terefleksi di browser.</p>
                  </div>
                </div>
                <button
                  type="button"
                  :disabled="cacheLoading !== null"
                  class="w-full rounded-lg border border-blue-400/40 bg-blue-50 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                  @click="cacheAction('views', 'Clear views')"
                >
                  <RefreshCw class="h-3.5 w-3.5" :class="cacheLoading === 'views' ? 'animate-spin' : ''" />
                  {{ cacheLoading === 'views' ? 'Membersihkan…' : 'Clear View Cache' }}
                </button>
              </div>

              <div class="rounded-xl border border-line bg-ivory/60 p-5 space-y-3">
                <div class="flex items-start gap-3">
                  <span class="grid h-9 w-9 flex-none place-items-center rounded-lg bg-purple-500/15 text-purple-600">
                    <Server class="h-4 w-4" />
                  </span>
                  <div>
                    <p class="text-sm font-semibold text-forest">Reload OPcache PHP</p>
                    <p class="text-xs text-muted leading-relaxed mt-0.5">Kirim sinyal restart PHP OPcache / LiteSpeed LSPHP. Berguna setelah deploy agar script PHP terbaru langsung aktif.</p>
                  </div>
                </div>
                <button
                  type="button"
                  :disabled="cacheLoading !== null"
                  class="w-full rounded-lg border border-purple-400/40 bg-purple-50 py-2 text-xs font-semibold text-purple-700 hover:bg-purple-100 transition disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                  @click="cacheAction('opcache', 'Reload OPcache')"
                >
                  <RefreshCw class="h-3.5 w-3.5" :class="cacheLoading === 'opcache' ? 'animate-spin' : ''" />
                  {{ cacheLoading === 'opcache' ? 'Mengirim sinyal…' : 'Reload OPcache / LSPHP' }}
                </button>
              </div>
            </div>
          </div>

          <!-- System Info Panel -->
          <div class="rounded-xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-6 py-5">
              <h2 class="font-display text-xl text-forest flex items-center gap-2">
                <Info class="h-5 w-5 text-gold" />Informasi Server & Lingkungan Aplikasi
              </h2>
              <p class="text-xs text-muted mt-0.5">Detail teknis spesifikasi server dan konfigurasi runtime saat ini.</p>
            </div>
            <div class="p-6 sm:p-7">
              <dl class="grid gap-3 sm:grid-cols-2">
                <div v-for="(val, key) in {
                  'Versi PHP': systemInfo.php_version,
                  'Versi Laravel': systemInfo.laravel_version,
                  'Driver Cache': systemInfo.cache_driver,
                  'Queue Connection': systemInfo.queue_connection,
                  'App Environment': systemInfo.app_env,
                  'Debug Mode': systemInfo.app_debug ? '⚠️ Aktif (production unsafe)' : '✅ Nonaktif',
                  'OPcache': systemInfo.opcache_active ? '✅ Aktif' : '❌ Tidak aktif',
                  'Storage Symlink': systemInfo.storage_linked ? '✅ Terhubung' : '❌ Belum terhubung',
                  'IP Anda Sekarang': systemInfo.client_ip,
                  'Web Server': systemInfo.server_software,
                }" :key="key"
                  class="flex items-start justify-between gap-3 rounded-lg border border-line bg-ivory/50 px-4 py-3"
                >
                  <dt class="text-xs text-muted font-medium flex-none w-36">{{ key }}</dt>
                  <dd class="text-xs font-mono text-forest text-right break-all">{{ val ?? '–' }}</dd>
                </div>
              </dl>

              <!-- Warning if debug is on in production -->
              <div v-if="systemInfo.app_debug && systemInfo.app_env === 'production'" class="mt-5 rounded-xl border border-amber-400/40 bg-amber-50/70 p-4 flex items-start gap-3">
                <AlertTriangle class="h-4 w-4 text-amber-600 flex-none mt-0.5" />
                <div>
                  <p class="text-xs font-semibold text-amber-700">Debug Mode aktif di lingkungan Production!</p>
                  <p class="text-xs text-amber-600 mt-0.5">Dapat mengekspos credential ke pengunjung saat error. Segera ubah <code class="font-mono bg-amber-100 px-1 rounded">APP_DEBUG=false</code> di file <code class="font-mono bg-amber-100 px-1 rounded">.env</code> server.</p>
                </div>
              </div>
            </div>
          </div>

        </section>

        <!-- ════════════ BOTTOM SAVE BUTTON ════════════ -->
        <div v-if="activeTab !== 'maintenance'" class="flex items-center justify-end gap-3 pt-3">
          <AppButton size="sm" :loading="form.processing" @click="save">
            <template #icon><Save class="h-4 w-4" /></template>
            Simpan Pengaturan
          </AppButton>
        </div>

      </main>
    </div>
  </div>
</template>
