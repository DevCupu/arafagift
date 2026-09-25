<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowRight, Check, ChevronLeft, ChevronRight, Gift, MessageCircle, Shield, Truck } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import SectionHeader from '@/components/ui/SectionHeader.vue'
import ProductCard from '@/components/storefront/ProductCard.vue'
import CategoryCard from '@/components/storefront/CategoryCard.vue'
import OccasionCard from '@/components/storefront/OccasionCard.vue'
import ValueProps from '@/components/storefront/ValueProps.vue'
import TestimonialGrid from '@/components/storefront/TestimonialGrid.vue'
import InstagramGrid from '@/components/storefront/InstagramGrid.vue'
import FaqAccordion from '@/components/storefront/FaqAccordion.vue'
import QuickView from '@/components/storefront/QuickView.vue'
import heroImgFallback from '@/assets/hero.webp'
import { formatIDR } from '@/composables/useFormat'
import { useStore } from '@/composables/useStore'

const props = defineProps({
  categories: { type: Array, required: true },
  occasions: { type: Array, required: true },
  featuredProducts: { type: Array, required: true },
  signatureProduct: { type: Object, default: null },
  content: { type: Object, required: true },
  testimonials: { type: Array, required: true },
  faqs: { type: Array, required: true },
})

const { whatsappHref } = useStore()

const homeContent = props.content

const values = homeContent.values ?? [
  { icon: 'Sparkles', title: 'Curated with Care', body: 'Kami mencicipi, memegang, dan memakai sendiri semua yang dijual sebelum masuk katalog.' },
  { icon: 'Gift', title: 'Elegant Packaging', body: 'Box hardcover, sleeve kertas tebal, dan kartu tulis tangan. Tidak perlu dibungkus ulang.' },
  { icon: 'BadgeCheck', title: 'Quality Products', body: 'Kurma disortir manual, madu diuji lab, sajadah ditenun bukan dicetak.' },
  { icon: 'Send', title: 'Ready to Gift', body: 'Bisa dikirim langsung ke alamat penerima tanpa nota harga di dalam paket.' },
]
const signatureProduct = props.signatureProduct
const hero = homeContent.hero
const heroImg = hero.image || heroImgFallback
const headlineLines = computed(() => hero.headline.split('\n'))
const quickview = ref(null)
const adRail = ref(null)
const activeAd = ref(0)
let adTimer = null

const adSlides = computed(() => [
  {
    image: heroImg,
    badge: 'Koleksi Pilihan',
    title: 'Hadiah yang sampai bersama doa.',
    body: 'Pilihan oleh-oleh elegan untuk keluarga dan orang-orang terkasih.',
    cta: 'Jelajahi koleksi',
    href: '/koleksi',
  },
  {
    image: '/images/assets/gift-worth-remembering.webp',
    badge: 'Signature Gift Set',
    title: 'Satu box untuk momen yang diingat.',
    body: 'Isi lengkap, kemasan rapi, siap diberikan tanpa perlu dibungkus ulang.',
    cta: 'Lihat gift set',
    href: '/koleksi/gift-set',
  },
  {
    image: '/images/assets/souvenir-satu-rombongan.webp',
    badge: 'Untuk Rombongan',
    title: 'Souvenir seragam, rapi, dan berkesan.',
    body: 'Pesan mulai 50 pcs dengan kartu nama jamaah dan pengiriman terkoordinasi.',
    cta: 'Konsultasi sekarang',
    href: '/faq',
  },
])

const scrollAds = (index) => {
  const nextIndex = (index + adSlides.value.length) % adSlides.value.length
  activeAd.value = nextIndex
  adRail.value?.children[nextIndex]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' })
}

const pauseAds = () => window.clearInterval(adTimer)
const resumeAds = () => {
  window.clearInterval(adTimer)
  adTimer = window.setInterval(() => scrollAds(activeAd.value + 1), 5500)
}

onMounted(() => {
  resumeAds()
})

onUnmounted(() => window.clearInterval(adTimer))

const bulkCtaHref = computed(() => {
  const href = homeContent.bulk.cta.href
  if (!href || href.includes('6281234567890')) {
    return whatsappHref('Halo ArafahGift, saya mau konsultasi souvenir rombongan.')
  }
  return href
})
</script>

<template>
  <Head title="Oleh-Oleh Haji &amp; Umrah Elegan">
    <meta name="description" content="ArafahGift.id — toko oleh-oleh haji &amp; umrah: kurma premium, sajadah, tasbih, kalung, sarung, dan gift set elegan untuk keluarga, sahabat, dan rombongan." />
    <link rel="canonical" href="/" />
    <meta property="og:title" content="ArafahGift.id — Oleh-Oleh Haji &amp; Umrah Elegan" />
    <meta property="og:description" content="Kurma premium, sajadah, tasbih, kalung, sarung, dan gift set hadiah haji umrah dengan packaging elegan." />
  </Head>
  <div>
    <!-- ============ HERO ============ -->
    <!--
      Kiri : bg dark green, teks ivory & emas, dua CTA & trust badges
      Kanan: foto full-bleed tanpa frame, gradient kiri & vignette untuk kedalaman
      Fade-in murni opacity (tanpa menggeser layout), berjenjang per elemen.
    -->
    <section class="relative overflow-hidden bg-forest-deep" style="min-height: min(92svh, 720px);">
      <!-- Grain texture overlay -->
      <div class="grain pointer-events-none absolute inset-0 opacity-25" />

      <!-- ── RIGHT: Foto full-bleed (absolute, kanan) ── -->
      <div
        class="absolute inset-y-0 right-0 w-full lg:w-[52%]"
        aria-hidden="true"
      >
        <img
          :src="heroImg"
          alt="Koleksi oleh-oleh Umrah & Hajj ArafahGift"
          class="h-full w-full object-cover object-center"
          loading="eager"
          fetchpriority="high"
        />
        <!-- Gradient kiri foto → blend ke hijau tua -->
        <div class="absolute inset-y-0 left-0 w-[58%] bg-gradient-to-r from-forest-deep via-forest-deep/60 to-transparent" />
        <!-- Vignette halus di kanan foto biar ada kedalaman -->
        <div class="absolute inset-y-0 right-0 w-[28%] bg-gradient-to-l from-forest-deep/50 to-transparent" />
        <!-- Gradient bawah foto -->
        <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-forest-deep/60 to-transparent" />
      </div>

      <!-- ── LEFT: Konten teks ── -->
      <div class="shell relative flex h-full flex-col justify-center py-16 sm:py-20 lg:py-24">
        <div class="w-full max-w-[560px]">

          <!-- Eyebrow -->
          <div class="hero-enter flex items-center gap-3">
            <span class="h-px w-8 bg-gold/70" />
            <span class="h-1.5 w-1.5 rotate-45 bg-gold" />
            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.25em] text-gold">
              {{ hero.eyebrow }}
            </p>
          </div>

          <!-- Headline: Poppins, baris terakhir emas -->
          <h1
            class="hero-enter mt-5 sm:mt-6"
            style="font-size: clamp(2.5rem, 6vw, 4.4rem); line-height: 1.04; font-weight: 600; letter-spacing: -0.02em; animation-delay: 80ms;"
          >
            <span
              v-for="(line, i) in headlineLines" :key="i"
              class="block"
              :class="i === headlineLines.length - 1 ? 'text-gold' : 'text-ivory'"
            >{{ line }}</span>
          </h1>

          <!-- Sub -->
          <p
            class="hero-enter mt-6 max-w-[32rem] text-[1.05rem] leading-relaxed text-ivory/75 sm:text-[1.1rem]"
            style="animation-delay: 160ms;"
          >
            {{ hero.sub }}
          </p>

          <!-- CTA buttons -->
          <div class="hero-enter mt-8 flex flex-wrap items-center gap-3 sm:mt-10" style="animation-delay: 240ms;">
            <AppButton :to="hero.cta.to" variant="gold" size="lg">
              {{ hero.cta.label }}
              <template #icon><ArrowRight class="h-4 w-4" /></template>
            </AppButton>
            <AppButton
              :to="hero.ctaSecondary.to"
              size="lg"
              class="group !border-ivory/35 !bg-transparent !text-ivory hover:!bg-ivory/10"
            >
              {{ hero.ctaSecondary.label }}
              <template #icon>
                <span class="transition-transform duration-300 ease-calm group-hover:translate-x-0.5">
                  <ArrowRight class="h-4 w-4" />
                </span>
              </template>
            </AppButton>
          </div>

          <!-- Trust badges dengan separator vertikal -->
          <div class="hero-enter mt-10 flex flex-wrap items-center gap-0 divide-x divide-ivory/15 sm:mt-12" style="animation-delay: 320ms;">
            <div class="flex items-center gap-2 pr-5 sm:pr-6">
              <Truck class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
              <div class="text-[0.73rem] leading-tight text-ivory/55">
                Dikirim dari<br/><strong class="font-semibold text-ivory/80">Jakarta</strong>
              </div>
            </div>
            <div class="flex items-center gap-2 px-5 sm:px-6">
              <Gift class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
              <div class="text-[0.73rem] leading-tight text-ivory/55">
                Kartu Ucapan<br/><strong class="font-semibold text-ivory/80">Gratis</strong>
              </div>
            </div>
            <div class="flex items-center gap-2 pl-5 sm:pl-6">
              <Shield class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
              <div class="text-[0.73rem] leading-tight text-ivory/55">
                Produk<br/><strong class="font-semibold text-ivory/80">Terpercaya</strong>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- ============ AD SLIDER ============ -->
    <section class="border-y border-line bg-surface py-8 sm:py-12" aria-label="Promo ArafahGift">
      <div class="shell">
        <div class="relative overflow-hidden rounded-[1rem] border border-line bg-forest-deep shadow-soft" v-reveal>
          <div
            ref="adRail"
            class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto"
            @mouseenter="pauseAds"
            @mouseleave="resumeAds"
          >
            <a
              v-for="slide in adSlides"
              :key="slide.title"
              :href="slide.href"
              class="relative min-w-full snap-start overflow-hidden"
            >
              <img :src="slide.image" :alt="slide.title" class="absolute inset-0 h-full w-full object-cover opacity-55" loading="lazy" />
              <div class="absolute inset-0 bg-gradient-to-r from-forest-deep via-forest-deep/80 to-forest-deep/25" />
              <div class="relative z-10 flex min-h-[300px] items-end p-6 sm:min-h-[340px] sm:p-10 lg:min-h-[380px] lg:p-14">
                <div class="max-w-xl">
                  <p class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-gold">{{ slide.badge }}</p>
                  <h2 class="mt-4 max-w-lg text-[2rem] leading-[1.05] text-ivory sm:text-[3.1rem]">{{ slide.title }}</h2>
                  <p class="mt-4 max-w-md text-[0.9rem] leading-relaxed text-ivory/70 sm:text-base">{{ slide.body }}</p>
                  <span class="mt-6 inline-flex min-h-11 items-center gap-2 bg-gold px-5 py-2.5 text-[0.78rem] font-semibold text-forest-deep">{{ slide.cta }} <ArrowRight class="h-4 w-4" /></span>
                </div>
              </div>
            </a>
          </div>
          <div class="absolute bottom-6 right-6 z-20 flex items-center gap-2 sm:bottom-8 sm:right-10">
            <button
              type="button"
              class="grid h-9 w-9 place-items-center border border-ivory/30 bg-forest-deep/50 text-ivory backdrop-blur transition hover:border-gold hover:text-gold active:scale-95"
              aria-label="Iklan sebelumnya"
              title="Iklan sebelumnya"
              @click.prevent="scrollAds(activeAd - 1)"
            >
              <ChevronLeft class="h-4 w-4" />
            </button>
            <button
              type="button"
              class="grid h-9 w-9 place-items-center border border-ivory/30 bg-forest-deep/50 text-ivory backdrop-blur transition hover:border-gold hover:text-gold active:scale-95"
              aria-label="Iklan berikutnya"
              title="Iklan berikutnya"
              @click.prevent="scrollAds(activeAd + 1)"
            >
              <ChevronRight class="h-4 w-4" />
            </button>
          </div>
          <div class="absolute bottom-9 left-6 z-20 flex gap-1.5 sm:bottom-11 sm:left-10">
            <button
              v-for="(_, i) in adSlides"
              :key="i"
              type="button"
              class="h-1.5 transition-all"
              :class="i === activeAd ? 'w-8 bg-gold' : 'w-3 bg-ivory/45 hover:bg-ivory/75'"
              :aria-label="`Tampilkan iklan ${i + 1}`"
              @click="scrollAds(i)"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- ============ OCCASION ============ -->
    <section class="shell py-16 sm:py-24">
      <SectionHeader
        title="Untuk siapa hadiah ini?"
        sub="Kadang lebih mudah memulai dari orangnya, bukan dari produknya."
        v-reveal
      />
      <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" v-reveal>
        <div
          v-for="(o, i) in occasions"
          :key="o.slug"
          class="reveal-child"
          :style="{ '--reveal-delay': `${i * 60}ms` }"
        >
          <OccasionCard :occasion="o" />
        </div>
      </div>
    </section>

    <!-- ============ KATEGORI ============ -->
    <section class="shell py-16 sm:py-24">
      <div class="flex flex-wrap items-end justify-between gap-6" v-reveal>
        <SectionHeader title="Mulai dari yang paling dicari" />
        <Link href="/koleksi" class="link-underline hidden text-[0.85rem] text-forest sm:block">
          Lihat semua koleksi
        </Link>
      </div>
      <div
        class="mt-10 grid grid-cols-2 gap-4 sm:mt-12 sm:grid-cols-3 sm:gap-6 lg:grid-cols-6"
        v-reveal
      >
        <div
          v-for="(c, i) in categories" :key="c.slug"
          class="reveal-child"
          :style="{ '--reveal-delay': `${i * 60}ms` }"
        >
          <CategoryCard :category="c" :index="i" />
        </div>
      </div>
    </section>

    <!-- ============ FEATURED ============ -->
    <section class="shell py-16 sm:py-24">
      <div class="flex flex-wrap items-end justify-between gap-6" v-reveal>
        <SectionHeader title="Favorit dari ArafahGift" sub="Yang paling sering dibawa pulang — dan paling sering dipesan ulang." />
        <Link href="/koleksi" class="link-underline hidden text-[0.85rem] text-forest sm:block">Lihat semua produk</Link>
      </div>
      <div class="mt-14 grid grid-cols-2 gap-x-5 gap-y-12 lg:grid-cols-4 lg:gap-x-8" v-reveal>
        <div
          v-for="(p, i) in featuredProducts.slice(0, 4)"
          :key="p.id" class="reveal-child"
          :style="{ '--reveal-delay': `${i * 70}ms` }"
        >
          <ProductCard
            :product="p" :index="i"
            @quickview="quickview = $event"
          />
        </div>
      </div>
      <div class="mt-10 sm:hidden">
        <AppButton to="/koleksi" variant="outline" block>Lihat semua produk</AppButton>
      </div>
    </section>

    <!-- ============ SIGNATURE GIFT SET ============ -->
    <!-- Band hijau tua berikutnya (Value Props) dipisahkan dengan section ini
         yang dijadikan light editorial agar tidak ada dua band gelap berurutan -->
    <section v-if="signatureProduct" class="border-y border-line bg-surface">
      <div class="shell grid items-center gap-12 py-16 sm:py-24 lg:grid-cols-2 lg:gap-20">
        <div class="relative pb-4 pl-4 sm:pb-6 sm:pl-6" v-reveal>
          <!-- Frame emas offset di belakang gambar -->
          <div class="absolute bottom-0 left-0 h-[92%] w-[94%] rounded-[1rem] border border-gold/45" aria-hidden="true" />
          <div class="arch arch--deep relative overflow-hidden border border-line bg-forest-deep">
            <img
              src="/images/assets/gift-worth-remembering.webp"
              alt="Gift set ArafahGift diserahkan sebagai hadiah"
              class="aspect-[5/6] w-full object-contain"
              loading="lazy"
              decoding="async"
            />
          </div>
          <div class="absolute -right-3 bottom-12 rounded-[0.5rem] border border-gold/40 bg-forest-deep px-5 py-3">
            <p class="text-[0.62rem] uppercase tracking-[0.16em] text-gold">Mulai</p>
            <p class="mt-1 font-display text-xl text-ivory">{{ formatIDR(signatureProduct.price) }}</p>
          </div>
        </div>

        <div v-reveal="120">
          <p class="eyebrow">{{ homeContent.signature.eyebrow }}</p>
          <h2 class="mt-6 max-w-lg text-[2.4rem] leading-[1.05] sm:text-[3.1rem]">{{ homeContent.signature.title }}</h2>
          <p class="mt-6 max-w-md text-[0.95rem] leading-relaxed text-muted">{{ homeContent.signature.body }}</p>

          <ul class="mt-9 max-w-sm divide-y divide-line border-y border-line">
            <li v-for="item in signatureProduct.includes" :key="item" class="flex items-center gap-3 py-3.5 text-[0.88rem] text-ink">
              <span class="h-1 w-1 rotate-45 bg-gold" />{{ item }}
            </li>
          </ul>

          <AppButton :to="homeContent.signature.cta.to" variant="gold" size="lg" class="mt-9">
            {{ homeContent.signature.cta.label }}
            <template #icon><ArrowRight class="h-4 w-4" /></template>
          </AppButton>
        </div>
      </div>
    </section>

    <!-- ============ VALUE PROPS ============ -->
    <!-- Nilai inti + momen keluarga, frame emas offset + caption melayang
         biar terasa editorial premium tanpa menambah band gelap -->
    <section class="bg-forest">
      <div class="shell py-20 sm:py-28">
        <SectionHeader
          eyebrow="Kenapa ArafahGift"
          title="Lebih dari sekadar oleh-oleh."
          align="center"
          :dark="true"
          v-reveal
        />
        <div class="mt-14" v-reveal><ValueProps :items="values" :dark="true" numbered /></div>

        <!-- Momen keluarga membuka gift set bersama -->
        <figure class="relative pb-4 pl-4 sm:pb-6 sm:pl-6" v-reveal>
          <div class="absolute bottom-0 left-0 h-[93%] w-[96%] rounded-[1rem] border border-gold/30" aria-hidden="true" />
          <div class="arch relative overflow-hidden border border-ivory/15">
            <img
              src="/images/assets/section-lebih-dari-oleh-oleh.webp"
              alt="Keluarga membuka gift set ArafahGift bersama"
              class="aspect-[16/9] w-full object-cover"
              loading="lazy"
              decoding="async"
            />
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-forest-deep/75 to-transparent" />
            <figcaption class="absolute inset-x-4 bottom-4 flex items-center justify-center gap-2.5 sm:inset-x-10 sm:bottom-6 sm:gap-4">
              <span class="h-px w-5 bg-gold/70 sm:w-10" />
              <span class="rounded-sm border border-ivory/20 bg-forest-deep/85 px-3.5 py-2 text-center text-[0.64rem] uppercase leading-relaxed tracking-[0.14em] text-ivory backdrop-blur-sm sm:px-5 sm:text-[0.72rem]">
                Keluarga membuka gift set ArafahGift bersama
              </span>
              <span class="h-px w-5 bg-gold/70 sm:w-10" />
            </figcaption>
          </div>
        </figure>
      </div>
    </section>

    <!-- ============ ROMBONGAN ============ -->
    <section class="shell pb-16 sm:pb-24">
      <div class="grid items-stretch gap-px overflow-hidden rounded-[1rem] border border-line bg-forest-deep shadow-soft lg:grid-cols-[1.2fr_1fr]" v-reveal>
        <div class="bg-forest p-9 sm:p-14">
          <p class="eyebrow text-gold">{{ homeContent.bulk.eyebrow }}</p>
          <h2 class="mt-6 max-w-md text-[2.1rem] leading-[1.08] text-ivory sm:text-[2.7rem]">{{ homeContent.bulk.title }}</h2>
          <p class="mt-5 max-w-md text-[0.95rem] leading-relaxed text-ivory/65">{{ homeContent.bulk.sub }}</p>
          <ul class="mt-8 space-y-3">
            <li v-for="p in homeContent.bulk.points" :key="p" class="flex items-start gap-3 text-[0.88rem] text-ivory/85">
              <Check class="mt-0.5 h-4 w-4 flex-none text-gold" :stroke-width="1.6" />{{ p }}
            </li>
          </ul>
          <AppButton :href="bulkCtaHref" variant="gold" size="lg" class="mt-9" target="_blank" rel="noopener">
            {{ homeContent.bulk.cta.label }}
            <template #icon><MessageCircle class="h-4 w-4" /></template>
          </AppButton>
        </div>
        <div class="relative min-h-[320px] bg-forest-deep">
          <img
            src="/images/assets/souvenir-satu-rombongan.webp"
            alt="Souvenir seragam untuk pesanan rombongan"
            class="absolute inset-0 h-full w-full object-cover"
            loading="lazy"
            decoding="async"
          />
          <div class="absolute inset-x-8 bottom-8 rounded-[0.5rem] border border-forest-soft/40 bg-forest-deep/90 px-5 py-4 backdrop-blur">
            <p class="font-display text-[1.15rem] leading-snug text-ivory">240 pouch untuk rombongan Solo</p>
            <p class="mt-1.5 text-[0.75rem] text-ivory/55">Dicetak nama jamaah, dikirim tepat sebelum keberangkatan.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ BRAND STORY ============ -->
    <section class="border-y border-forest/20 bg-forest-deep/[0.04]">
      <div class="shell grid gap-12 py-16 lg:grid-cols-[1fr_1.1fr] lg:gap-20 lg:py-24">
        <div class="flex gap-4" v-reveal>
          <div class="arch mt-10 h-56 w-1/2 overflow-hidden border border-forest/20 bg-forest/[0.07]">
            <img src="/images/assets/img-4.webp" alt="Persiapan oleh-oleh sebelum pulang" class="h-full w-full object-cover" loading="lazy" decoding="async" />
          </div>
          <div class="arch h-64 w-1/2 overflow-hidden border border-forest/20 bg-forest/[0.07]">
            <img
              src="/images/assets/perjalanan-pulang-membawa-cerita.webp"
              alt="Perlengkapan perjalanan dan gift set ArafahGift"
              class="h-full w-full object-cover"
              loading="lazy"
              decoding="async"
            />
          </div>
        </div>
        <div v-reveal="100">
          <p class="eyebrow">{{ homeContent.story.eyebrow }}</p>
          <h2 class="mt-6 max-w-lg text-[2.1rem] leading-[1.08] sm:text-[2.7rem]">{{ homeContent.story.title }}</h2>
          <div class="mt-6 max-w-lg space-y-4 text-[0.95rem] leading-relaxed text-muted">
            <p v-for="(par, i) in homeContent.story.body" :key="i">{{ par }}</p>
          </div>
          <p class="mt-8 font-display text-lg italic text-forest">{{ homeContent.story.signature }}</p>
          <Link href="/tentang" class="link-underline mt-6 inline-block text-[0.85rem] text-forest">
            Baca cerita lengkapnya
          </Link>
        </div>
      </div>
    </section>

    <!-- ============ TESTIMONI ============ -->
    <section class="shell py-16 sm:py-24">
      <SectionHeader title="Yang mereka ceritakan" align="center" v-reveal />
      <div class="mt-14" v-reveal><TestimonialGrid :items="testimonials" /></div>
    </section>

    <!-- ============ INSTAGRAM ============ -->
    <section class="shell py-16 sm:py-24" v-reveal>
      <InstagramGrid :content="homeContent.instagram" />
    </section>

    <!-- ============ FAQ ============ -->
    <section class="shell py-16 sm:py-24">
      <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20" v-reveal>
        <div>
          <SectionHeader title="Pertanyaan yang sering masuk" />
          <Link href="/faq" class="link-underline mt-6 inline-block text-[0.85rem] text-forest">
            Lihat semua pertanyaan
          </Link>
        </div>
        <FaqAccordion :items="faqs.slice(0, 4)" />
      </div>
    </section>

    <QuickView :product="quickview" @close="quickview = null" />
  </div>
</template>
