<script setup>
import { ref } from 'vue'
import { ArrowRight, Check, Gift, Shield, Truck } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import SectionHeader from '@/components/ui/SectionHeader.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import ProductCard from '@/components/storefront/ProductCard.vue'
import CategoryCard from '@/components/storefront/CategoryCard.vue'
import OccasionCard from '@/components/storefront/OccasionCard.vue'
import ValueProps from '@/components/storefront/ValueProps.vue'
import TestimonialGrid from '@/components/storefront/TestimonialGrid.vue'
import InstagramGrid from '@/components/storefront/InstagramGrid.vue'
import FaqAccordion from '@/components/storefront/FaqAccordion.vue'
import QuickView from '@/components/storefront/QuickView.vue'
import heroImg from '@/assets/hero.png'
import { categories, featuredProducts, findProduct, occasions } from '@/data/catalog'
import { faqs, homeContent, testimonials, values } from '@/data/content'
import { formatIDR } from '@/composables/useFormat'

const hero = homeContent.hero
const signatureProduct = findProduct(homeContent.signature.productSlug)
const quickview = ref(null)
</script>

<template>
  <div>
    <!-- ============ HERO ============ -->
    <!--
      Layout persis referensi:
      • Kiri  : bg dark green, teks putih & emas, dua CTA, trust badges
      • Kanan : foto hero.png full-bleed tanpa frame, bleed ke tepi layar
      • Gradient kiri memastikan teks tetap terbaca di overlap foto
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
        <div class="absolute inset-y-0 left-0 w-[55%] bg-gradient-to-r from-forest-deep via-forest-deep/80 to-transparent" />
        <!-- Gradient bawah foto -->
        <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-forest-deep/60 to-transparent" />
      </div>

      <!-- ── LEFT: Konten teks ── -->
      <div class="shell relative flex h-full flex-col justify-center py-20 sm:py-24 lg:py-28">
        <div class="w-full max-w-[540px]" v-reveal>

          <!-- Eyebrow -->
          <div class="flex items-center gap-3">
            <span class="h-px w-8 bg-gold" />
            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.25em] text-gold">
              {{ hero.eyebrow }}
            </p>
          </div>

          <!-- Headline: baris 1-2 putih, baris terakhir emas -->
          <h1
            class="mt-5 sm:mt-6"
            style="font-size: clamp(2.4rem, 6vw, 4.2rem); line-height: 1.05; font-weight: 700;"
          >
            <span class="block text-ivory">Hadiah dari</span>
            <span class="block text-ivory">Tanah Suci,</span>
            <span class="block text-gold">untuk hati yang dekat.</span>
          </h1>

          <!-- Sub -->
          <p class="mt-5 max-w-[28rem] text-[0.95rem] leading-relaxed text-ivory/60 sm:mt-6 sm:text-[1rem]">
            {{ hero.sub }}
          </p>

          <!-- CTA buttons -->
          <div class="mt-8 flex flex-wrap items-center gap-3 sm:mt-10">
            <AppButton :to="hero.cta.to" variant="gold" size="lg">
              {{ hero.cta.label }}
              <template #icon><ArrowRight class="h-4 w-4" /></template>
            </AppButton>
            <AppButton
              :to="hero.ctaSecondary.to"
              size="lg"
              class="!border-ivory/35 !bg-transparent !text-ivory hover:!bg-ivory/10"
            >{{ hero.ctaSecondary.label }}</AppButton>
          </div>

          <!-- Trust badges dengan separator vertikal -->
          <div class="mt-10 flex flex-wrap items-center gap-0 divide-x divide-ivory/15 sm:mt-12">
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

    <!-- ============ KATEGORI ============ -->
    <section class="shell pt-20 sm:pt-28">
      <div class="flex flex-wrap items-end justify-between gap-6" v-reveal>
        <SectionHeader eyebrow="Koleksi" title="Mulai dari yang paling dicari" />
        <router-link to="/koleksi" class="link-underline hidden text-[0.85rem] text-forest sm:block">
          Lihat semua koleksi
        </router-link>
      </div>
      <div
        class="mt-10 grid grid-cols-2 gap-4 sm:mt-12 sm:grid-cols-3 sm:gap-6 lg:grid-cols-6"
        v-reveal
      >
        <div v-for="(c, i) in categories" :key="c.slug">
          <CategoryCard :category="c" :index="i" />
        </div>
      </div>
    </section>

    <!-- ============ FEATURED ============ -->
    <section class="shell pt-24 sm:pt-32">
      <div class="flex flex-wrap items-end justify-between gap-6" v-reveal>
        <SectionHeader eyebrow="Best seller" title="Favorit dari ArafahGift" sub="Yang paling sering dibawa pulang — dan paling sering dipesan ulang." />
        <router-link to="/koleksi" class="link-underline hidden text-[0.85rem] text-forest sm:block">Lihat semua produk</router-link>
      </div>
      <div class="mt-14 grid grid-cols-2 gap-x-5 gap-y-12 lg:grid-cols-4 lg:gap-x-8" v-reveal>
        <ProductCard
          v-for="(p, i) in featuredProducts.slice(0, 4)"
          :key="p.id" :product="p" :index="i"
          @quickview="quickview = $event"
        />
      </div>
      <div class="mt-10 sm:hidden">
        <AppButton to="/koleksi" variant="outline" block>Lihat semua produk</AppButton>
      </div>
    </section>

    <!-- ============ SIGNATURE GIFT SET ============ -->
    <section class="mt-24 bg-forest text-ivory sm:mt-32">
      <div class="shell grid items-center gap-12 py-16 lg:grid-cols-2 lg:gap-20 lg:py-24">
        <div class="relative" v-reveal>
          <div class="arch arch--deep overflow-hidden border border-ivory/15">
            <div class="aspect-[5/6] w-full"><ProductArt art="giftset" dark /></div>
          </div>
          <div class="absolute -right-4 bottom-8 hidden border border-gold/40 bg-forest-deep px-5 py-3 sm:block">
            <p class="text-[0.62rem] uppercase tracking-[0.16em] text-gold">Mulai</p>
            <p class="mt-1 font-display text-xl text-ivory">{{ formatIDR(signatureProduct.price) }}</p>
          </div>
        </div>

        <div v-reveal="120">
          <p class="eyebrow">{{ homeContent.signature.eyebrow }}</p>
          <h2 class="mt-6 text-[2.4rem] leading-[1.05] text-ivory sm:text-[3.1rem]">{{ homeContent.signature.title }}</h2>
          <p class="mt-6 max-w-md text-[0.95rem] leading-relaxed text-ivory/70">{{ homeContent.signature.body }}</p>

          <ul class="mt-9 max-w-sm divide-y divide-ivory/12 border-y border-ivory/12">
            <li v-for="item in signatureProduct.includes" :key="item" class="flex items-center gap-3 py-3.5 text-[0.88rem] text-ivory/85">
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
    <section class="mt-24 bg-forest sm:mt-32">
      <div class="shell py-20 sm:py-28">
        <SectionHeader eyebrow="Kenapa ArafahGift" title="Lebih dari sekadar oleh-oleh." align="center" :dark="true" v-reveal />
        <div class="mt-14" v-reveal><ValueProps :items="values" :dark="true" /></div>
      </div>
    </section>

    <!-- ============ OCCASION ============ -->
    <section class="shell pt-24 sm:pt-32">
      <SectionHeader
        eyebrow="Pilih berdasarkan penerima" title="Untuk siapa hadiah ini?"
        sub="Kadang lebih mudah memulai dari orangnya, bukan dari produknya." v-reveal
      />
      <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" v-reveal>
        <OccasionCard v-for="o in occasions" :key="o.slug" :occasion="o" />
      </div>
    </section>

    <!-- ============ ROMBONGAN ============ -->
    <section class="shell pt-24 sm:pt-32">
      <div class="grid items-stretch gap-px overflow-hidden border border-forest/30 bg-forest-deep lg:grid-cols-[1.2fr_1fr]" v-reveal>
        <div class="bg-forest p-9 sm:p-14">
          <p class="eyebrow text-gold">{{ homeContent.bulk.eyebrow }}</p>
          <h2 class="mt-6 max-w-md text-[2.1rem] leading-[1.08] text-ivory sm:text-[2.7rem]">{{ homeContent.bulk.title }}</h2>
          <p class="mt-5 max-w-md text-[0.95rem] leading-relaxed text-ivory/65">{{ homeContent.bulk.sub }}</p>
          <ul class="mt-8 space-y-3">
            <li v-for="p in homeContent.bulk.points" :key="p" class="flex items-start gap-3 text-[0.88rem] text-ivory/85">
              <Check class="mt-0.5 h-4 w-4 flex-none text-gold" :stroke-width="1.6" />{{ p }}
            </li>
          </ul>
          <AppButton :href="homeContent.bulk.cta.href" variant="gold" size="lg" class="mt-9">
            {{ homeContent.bulk.cta.label }}
            <template #icon><MessageCircle class="h-4 w-4" /></template>
          </AppButton>
        </div>
        <div class="relative min-h-[320px] bg-forest-deep">
          <ProductArt art="souvenir" :tone="2" dark />
          <div class="absolute inset-x-8 bottom-8 border border-forest-soft/40 bg-forest-deep/90 px-5 py-4 backdrop-blur">
            <p class="font-display text-[1.15rem] leading-snug text-ivory">240 pouch untuk rombongan Solo</p>
            <p class="mt-1.5 text-[0.75rem] text-ivory/55">Dicetak nama jamaah, dikirim tepat sebelum keberangkatan.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ BRAND STORY ============ -->
    <section class="mt-24 border-y border-forest/20 bg-forest-deep/[0.04] sm:mt-32">
      <div class="shell grid gap-12 py-16 lg:grid-cols-[1fr_1.1fr] lg:gap-20 lg:py-24">
        <div class="flex gap-4" v-reveal>
          <div class="arch mt-10 h-56 w-1/2 overflow-hidden border border-forest/20 bg-forest/[0.07]"><ProductArt art="kurma" :tone="1" /></div>
          <div class="arch h-64 w-1/2 overflow-hidden border border-forest/20 bg-forest/[0.07]"><ProductArt art="tasbih" :tone="0" /></div>
        </div>
        <div v-reveal="100">
          <p class="eyebrow">{{ homeContent.story.eyebrow }}</p>
          <h2 class="mt-6 max-w-lg text-[2.1rem] leading-[1.08] sm:text-[2.7rem]">{{ homeContent.story.title }}</h2>
          <div class="mt-6 max-w-lg space-y-4 text-[0.95rem] leading-relaxed text-muted">
            <p v-for="(par, i) in homeContent.story.body" :key="i">{{ par }}</p>
          </div>
          <p class="mt-8 font-display text-lg italic text-forest">{{ homeContent.story.signature }}</p>
          <router-link to="/tentang" class="link-underline mt-6 inline-block text-[0.85rem] text-forest">
            Baca cerita lengkapnya
          </router-link>
        </div>
      </div>
    </section>

    <!-- ============ TESTIMONI ============ -->
    <section class="shell pt-24 sm:pt-32">
      <SectionHeader eyebrow="Dari pembeli" title="Yang mereka ceritakan" align="center" v-reveal />
      <div class="mt-14" v-reveal><TestimonialGrid :items="testimonials" /></div>
    </section>

    <!-- ============ INSTAGRAM ============ -->
    <section class="shell pt-24 sm:pt-32" v-reveal>
      <InstagramGrid :content="homeContent.instagram" />
    </section>

    <!-- ============ FAQ ============ -->
    <section class="shell pt-24 sm:pt-32">
      <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20" v-reveal>
        <div>
          <SectionHeader eyebrow="FAQ" title="Pertanyaan yang sering masuk" />
          <router-link to="/faq" class="link-underline mt-6 inline-block text-[0.85rem] text-forest">
            Lihat semua pertanyaan
          </router-link>
        </div>
        <FaqAccordion :items="faqs.slice(0, 4)" />
      </div>
    </section>

    <QuickView :product="quickview" @close="quickview = null" />
  </div>
</template>
