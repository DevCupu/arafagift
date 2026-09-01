<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Instagram, MessageCircle } from 'lucide-vue-next'
import BrandLogo from '@/components/storefront/BrandLogo.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'
import { useStore } from '@/composables/useStore'

const { push } = useToast()
const { whatsappHref } = useStore()
const email = ref('')
const sending = ref(false)

const columns = [
  {
    title: 'Belanja',
    links: [
      { label: 'Semua koleksi', to: '/koleksi' },
      { label: 'Gift Set', to: '/koleksi/gift-set' },
      { label: 'Kurma', to: '/koleksi/kurma' },
      { label: 'Souvenir rombongan', to: '/koleksi/souvenir-rombongan' },
    ],
  },
  {
    title: 'ArafahGift',
    links: [
      { label: 'Tentang kami', to: '/tentang' },
      { label: 'FAQ', to: '/faq' },
      { label: 'Akun saya', to: '/akun' },
      { label: 'Lacak pesanan', to: '/lacak-pesanan' },
    ],
  },
  {
    title: 'Informasi',
    links: [
      { label: 'Kebijakan privasi', to: '/legal/kebijakan-privasi' },
      { label: 'Syarat & ketentuan', to: '/legal/syarat-ketentuan' },
      { label: 'Pengiriman', to: '/legal/pengiriman-pengembalian' },
      { label: 'Pengembalian', to: '/legal/pengiriman-pengembalian' },
    ],
  },
]

const subscribe = () => {
  if (!email.value.includes('@')) {
    push('Masukkan alamat email yang benar, contoh nama@email.com')
    return
  }
  sending.value = true
  setTimeout(() => {
    sending.value = false
    email.value = ''
    push('Terima kasih. Kabar koleksi baru akan kami kirim ke email Anda.', { tone: 'success' })
  }, 700)
}
</script>

<template>
  <footer class="mt-24 border-t border-forest-soft/30 bg-forest-deep text-ivory/75">
    <div class="shell grid gap-12 py-16 lg:grid-cols-[1.4fr_2fr] lg:gap-20">
      <div>
        <BrandLogo tone="ivory" size="lg" />
        <p class="mt-6 max-w-sm font-display text-[1.45rem] leading-snug text-ivory/90">
          Oleh-oleh Umrah &amp; Hajj yang dipilih dengan hati.
        </p>
        <div class="mt-8 flex gap-3">
          <a
            href="https://www.instagram.com/arafahajiumrahgift/" target="_blank" rel="noopener"
            class="grid h-10 w-10 place-items-center border border-ivory/20 transition hover:border-gold hover:text-gold"
            aria-label="Instagram ArafahGift"
          ><Instagram class="h-4 w-4" :stroke-width="1.5" /></a>
          <a
            :href="whatsappHref()" target="_blank" rel="noopener"
            class="grid h-10 w-10 place-items-center border border-ivory/20 transition hover:border-gold hover:text-gold"
            aria-label="WhatsApp ArafahGift"
          ><MessageCircle class="h-4 w-4" :stroke-width="1.5" /></a>
        </div>
      </div>

      <div class="grid gap-10 sm:grid-cols-3">
        <div v-for="col in columns" :key="col.title">
          <h3 class="text-eyebrow font-medium uppercase text-gold">{{ col.title }}</h3>
          <ul class="mt-5 space-y-3">
            <li v-for="link in col.links" :key="link.label">
              <Link :href="link.to" class="text-[0.85rem] text-ivory/70 transition hover:text-ivory">
                {{ link.label }}
              </Link>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="shell border-t border-ivory/10 py-8 sm:py-10">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-md">
          <p class="font-display text-lg text-ivory sm:text-xl">Kabar koleksi &amp; musim keberangkatan</p>
          <p class="mt-1.5 text-[0.8rem] text-ivory/60">Satu email per bulan. Tidak lebih.</p>
        </div>
        <div class="flex w-full max-w-md flex-col gap-2 sm:flex-row">
          <label class="sr-only" for="footer-email">Alamat email</label>
          <input
            id="footer-email" v-model="email" type="email" placeholder="nama@email.com"
            class="w-full border border-ivory/20 bg-transparent px-4 py-3 text-[0.85rem] text-ivory placeholder:text-ivory/40 focus:border-gold focus:outline-none"
            @keyup.enter="subscribe"
          />
          <AppButton variant="gold" :loading="sending" class="sm:flex-none" @click="subscribe">Daftar</AppButton>
        </div>
      </div>
    </div>

    <div class="shell flex flex-col gap-2 border-t border-ivory/10 py-5 text-[0.72rem] text-ivory/45 pb-safe sm:flex-row sm:items-center sm:justify-between">
      <p>© {{ new Date().getFullYear() }} ArafahGift.id — Jakarta, Indonesia</p>
      <p>Dibuat untuk mereka yang menunggu di rumah.</p>
    </div>
  </footer>
</template>
