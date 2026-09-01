<script setup>
import { Head } from '@inertiajs/vue3'
import ProductArt from '@/components/art/ProductArt.vue'
import ValueProps from '@/components/storefront/ValueProps.vue'
import AppButton from '@/components/ui/AppButton.vue'
import SectionHeader from '@/components/ui/SectionHeader.vue'
import { useStore } from '@/composables/useStore'

const props = defineProps({ content: { type: Object, required: true } })
const homeContent = props.content
const { whatsappHref } = useStore()

const values = [
  { icon: 'Sparkles', title: 'Curated with Care', body: 'Kami mencicipi, memegang, dan memakai sendiri semua yang dijual sebelum masuk katalog.' },
  { icon: 'Gift', title: 'Elegant Packaging', body: 'Box hardcover, sleeve kertas tebal, dan kartu tulis tangan. Tidak perlu dibungkus ulang.' },
  { icon: 'BadgeCheck', title: 'Quality Products', body: 'Kurma disortir manual, madu diuji lab, sajadah ditenun bukan dicetak.' },
  { icon: 'Send', title: 'Ready to Gift', body: 'Bisa dikirim langsung ke alamat penerima tanpa nota harga di dalam paket.' },
]

const milestones = [
  ['2021', 'Mulai dari satu koper titipan tetangga.'],
  ['2023', 'Membuka workshop pengemasan sendiri di Jakarta.'],
  ['2025', 'Melayani 40+ travel umrah untuk souvenir rombongan.'],
  ['2026', 'Lebih dari 12.000 hadiah dikirim ke seluruh Indonesia.'],
]
</script>

<template>
  <div>
    <Head title="Tentang Kami">
      <meta name="description" content="ArafahGift.id — cerita di balik toko oleh-oleh haji &amp; umrah yang mengemas kurma, sajadah, tasbih, dan gift set dengan hati untuk keluarga dan rombongan Anda." />
      <link rel="canonical" href="/tentang" />
      <meta property="og:title" content="Tentang Kami — ArafahGift.id" />
      <meta property="og:description" content="Cerita di balik toko oleh-oleh haji &amp; umrah ArafahGift.id." />
    </Head>
    <section class="shell grid items-center gap-12 py-14 lg:grid-cols-2 lg:gap-20 lg:py-20">
      <div>
        <p class="eyebrow">Tentang kami</p>
        <h1 class="mt-6 text-[2.6rem] leading-[1.04] sm:text-[3.4rem]">{{ homeContent.story.title }}</h1>
        <div class="mt-7 max-w-lg space-y-5 text-[0.98rem] leading-[1.8] text-muted">
          <p v-for="(par, i) in homeContent.story.body" :key="i">{{ par }}</p>
          <p>
            Semua yang kami jual melewati satu aturan sederhana: kalau kami tidak mau memberikannya
            kepada orang tua kami sendiri, barang itu tidak masuk katalog.
          </p>
        </div>
        <p class="mt-8 font-display text-lg italic text-forest">{{ homeContent.story.signature }}</p>
      </div>
      <div class="flex gap-4">
        <div class="arch mt-10 h-72 w-1/2 overflow-hidden border border-line bg-surface"><ProductArt art="sajadah" :tone="1" /></div>
        <div class="arch h-80 w-1/2 overflow-hidden border border-line bg-surface"><ProductArt art="madu" :tone="0" /></div>
      </div>
    </section>

    <section class="shell pt-8">
      <SectionHeader eyebrow="Perjalanan" title="Yang sudah kami lewati" />
      <!-- Urutan tahun benar-benar berurut, jadi penanda kronologis di sini membawa informasi -->
      <ol class="mt-12 grid gap-px overflow-hidden border border-line bg-line sm:grid-cols-2 lg:grid-cols-4">
        <li v-for="[year, text] in milestones" :key="year" class="bg-surface px-6 py-8">
          <p class="font-display text-3xl text-gold">{{ year }}</p>
          <p class="mt-4 text-[0.87rem] leading-relaxed text-muted">{{ text }}</p>
        </li>
      </ol>
    </section>

    <section class="shell pt-24">
      <SectionHeader eyebrow="Cara kerja kami" title="Empat hal yang tidak kami kompromikan" align="center" />
      <div class="mt-12"><ValueProps :items="values" /></div>
    </section>

    <section class="shell pt-24">
      <div class="border border-line bg-surface px-8 py-14 text-center">
        <h2 class="mx-auto max-w-xl text-[2rem] leading-tight sm:text-[2.5rem]">Sedang menyiapkan oleh-oleh untuk di rumah?</h2>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
          <AppButton to="/koleksi" size="lg">Jelajahi koleksi</AppButton>
          <AppButton :href="whatsappHref('Halo ArafahGift, saya mau konsultasi souvenir rombongan.')" variant="outline" size="lg" target="_blank" rel="noopener">Konsultasi rombongan</AppButton>
        </div>
      </div>
    </section>
  </div>
</template>
