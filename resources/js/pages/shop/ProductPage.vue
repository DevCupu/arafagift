<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Gift, Heart, PackageCheck, Truck } from 'lucide-vue-next'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppRating from '@/components/ui/AppRating.vue'
import QuantityInput from '@/components/ui/QuantityInput.vue'
import ProductGallery from '@/components/storefront/ProductGallery.vue'
import ProductCard from '@/components/storefront/ProductCard.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { formatIDR } from '@/composables/useFormat'
import { useCart } from '@/composables/useCart'
import { useWishlist } from '@/composables/useWishlist'
import { useStore } from '@/composables/useStore'

const props = defineProps({
  product: { type: Object, default: null },
  related: { type: Array, default: () => [] },
})
const cart = useCart()
const wishlist = useWishlist()
const { store, whatsappHref } = useStore()

const qty = ref(1)
const tab = ref('deskripsi')
watch(() => props.product?.slug, () => { qty.value = 1; tab.value = 'deskripsi' })

const soldOut = computed(() => props.product?.stock === 0)
const lowStock = computed(() => props.product && props.product.stock > 0 && props.product.stock <= (props.product.lowStock ?? 10))

const tabs = [
  { id: 'deskripsi', label: 'Deskripsi' },
  { id: 'isi', label: 'Isi paket' },
  { id: 'detail', label: 'Detail' },
  { id: 'ulasan', label: 'Ulasan' },
]

const buyNow = () => {
  cart.add(props.product, qty.value, { silent: true })
  router.visit('/checkout')
}

const metaDescription = computed(() => props.product?.short || props.product?.description?.slice(0, 160) || '')
const productJsonLd = computed(() => {
  if (!props.product) return null
  return {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: props.product.name,
    image: props.product.image ? [props.product.image] : undefined,
    description: metaDescription.value,
    sku: props.product.sku,
    category: props.product.category,
    offers: {
      '@type': 'Offer',
      priceCurrency: 'IDR',
      price: props.product.price,
      availability: props.product.stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
      url: `/produk/${props.product.slug}`,
    },
    aggregateRating: props.product.reviews > 0 ? {
      '@type': 'AggregateRating',
      ratingValue: props.product.rating,
      reviewCount: props.product.reviews,
    } : undefined,
  }
})
</script>

<template>
  <div v-if="product">
    <Head :title="product.name">
      <meta name="description" :content="metaDescription" />
      <link rel="canonical" :href="`/produk/${product.slug}`" />
      <meta property="og:type" content="product" />
      <meta property="og:title" :content="product.name" />
      <meta property="og:description" :content="metaDescription" />
      <meta v-if="product.image" property="og:image" :content="product.image" />
      <component :is="'script'" type="application/ld+json">{{ JSON.stringify(productJsonLd) }}</component>
    </Head>
    <div class="shell pt-8">
      <nav class="flex flex-wrap items-center gap-2 text-[0.72rem] uppercase tracking-[0.14em] text-muted" aria-label="Breadcrumb">
        <Link href="/" class="transition hover:text-forest">Home</Link>
        <span class="text-gold">/</span>
        <Link :href="`/koleksi/${product.categorySlug}`" class="transition hover:text-forest">{{ product.category }}</Link>
        <span class="text-gold">/</span>
        <span class="text-forest">{{ product.name }}</span>
      </nav>
    </div>

    <div class="shell grid gap-12 py-10 lg:grid-cols-[1.05fr_1fr] lg:gap-20 lg:py-14">
      <div class="lg:sticky lg:top-28 lg:h-fit">
        <ProductGallery :product="product" />
      </div>

      <div>
        <div class="flex items-center gap-3">
          <p class="text-[0.68rem] uppercase tracking-[0.16em] text-olive">{{ product.category }}</p>
          <AppBadge v-if="product.badge">{{ product.badge }}</AppBadge>
        </div>

        <h1
          class="mt-4 text-balance leading-[1.05]"
          style="font-size: clamp(1.85rem, 6vw, 2.9rem);"
        >{{ product.name }}</h1>

        <div class="mt-4 flex items-center gap-4">
          <AppRating :value="product.rating" :count="product.reviews" size="md" />
          <a href="#ulasan" class="link-underline text-[0.78rem] text-muted" @click="tab = 'ulasan'">Baca ulasan</a>
        </div>

        <div class="mt-6 flex items-baseline gap-3">
          <span class="font-display text-[2.2rem] leading-none text-forest">{{ formatIDR(product.price) }}</span>
          <span v-if="product.comparePrice" class="text-[0.95rem] text-muted line-through">{{ formatIDR(product.comparePrice) }}</span>
        </div>

        <p class="mt-6 max-w-md text-[0.95rem] leading-relaxed text-muted">{{ product.short }}</p>

        <div class="mt-7 flex items-start gap-3 border border-dashed border-gold/40 bg-gold/[0.07] px-4 py-3.5">
          <Gift class="mt-0.5 h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
          <p class="text-[0.8rem] leading-relaxed text-forest">
            <span class="font-semibold">Kartu ucapan gratis.</span> Tulis pesan Anda di checkout — kami tulis tangan sebelum dikemas.
          </p>
        </div>

        <p v-if="lowStock" class="mt-5 text-[0.8rem] text-danger">Tinggal {{ product.stock }} tersisa</p>
        <p v-else-if="soldOut" class="mt-5 text-[0.8rem] text-danger">Stok habis. Hubungi kami untuk pre-order.</p>

        <div class="mt-6 flex flex-wrap items-center gap-3">
          <QuantityInput v-model="qty" :max="Math.max(product.stock, 1)" />
          <AppButton class="flex-1 sm:flex-none sm:min-w-[14rem]" size="lg" :disabled="soldOut" @click="cart.add(product, qty)">
            {{ soldOut ? 'Stok habis' : 'Tambah ke keranjang' }}
          </AppButton>
          <button
            class="grid h-[50px] w-[50px] place-items-center border border-line bg-surface transition hover:border-gold"
            :aria-label="wishlist.has(product.id) ? 'Hapus dari wishlist' : 'Simpan ke wishlist'"
            :aria-pressed="wishlist.has(product.id)"
            @click="wishlist.toggle(product.id)"
          >
            <Heart class="h-[18px] w-[18px]" :class="wishlist.has(product.id) ? 'fill-gold text-gold' : 'text-forest'" :stroke-width="1.5" />
          </button>
        </div>
        <AppButton variant="outline" size="lg" block class="mt-3" :disabled="soldOut" @click="buyNow">Beli sekarang</AppButton>

        <ul class="mt-9 divide-y divide-line border-y border-line text-[0.83rem] text-muted">
          <li class="flex items-center gap-3 py-3.5">
            <Truck class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
            Dikirim dari {{ store.originCity || 'Jakarta' }} · reguler 2–4 hari, same day untuk Jabodetabek
          </li>
          <li class="flex items-center gap-3 py-3.5">
            <PackageCheck class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
            Dikemas ulang di ruang bersih · nota harga tidak disertakan
          </li>
        </ul>
      </div>
    </div>

    <!-- Tabs -->
    <section id="ulasan" class="shell pt-8">
      <div class="no-scrollbar flex gap-8 overflow-x-auto border-b border-line">
        <button
          v-for="t in tabs" :key="t.id" type="button"
          class="relative whitespace-nowrap pb-4 text-[0.85rem] transition"
          :class="tab === t.id ? 'text-forest' : 'text-muted hover:text-forest'"
          @click="tab = t.id"
        >
          {{ t.label }}
          <span v-if="tab === t.id" class="absolute inset-x-0 -bottom-px h-px bg-gold" />
        </button>
      </div>

      <div class="grid gap-12 py-10 lg:grid-cols-[1.2fr_1fr] lg:gap-20">
        <div>
          <p v-if="tab === 'deskripsi'" class="max-w-xl text-[0.98rem] leading-[1.8] text-muted">{{ product.description }}</p>

          <ul v-else-if="tab === 'isi'" class="max-w-md divide-y divide-line border-y border-line">
            <li v-for="item in product.includes" :key="item" class="flex items-center gap-3 py-3.5 text-[0.9rem] text-forest">
              <span class="h-1 w-1 rotate-45 bg-gold" />{{ item }}
            </li>
          </ul>

          <dl v-else-if="tab === 'detail'" class="max-w-md divide-y divide-line border-y border-line">
            <div v-for="(row, i) in product.details" :key="i" class="flex justify-between gap-6 py-3.5 text-[0.88rem]">
              <dt class="text-muted">{{ row[0] }}</dt>
              <dd class="text-right text-forest">{{ row[1] }}</dd>
            </div>
          </dl>

          <div v-else class="max-w-2xl">
            <div class="flex flex-wrap items-center gap-6 border border-line bg-surface px-6 py-5">
              <div>
                <p class="font-display text-4xl text-forest">{{ product.rating.toFixed(1) }}</p>
                <AppRating :value="product.rating" class="mt-2" />
              </div>
              <p class="text-[0.83rem] text-muted">Dari {{ product.reviews }} ulasan pembeli terverifikasi.</p>
            </div>
            <div class="mt-10 border border-dashed border-line px-6 py-12 text-center">
              <p class="font-display text-xl text-forest">Ulasan akan ditampilkan di sini</p>
              <p class="mt-2 text-[0.85rem] text-muted">
                Cerita dari pembeli terverifikasi muncul di tab ini begitu sudah terkumpul.
              </p>
            </div>
          </div>
        </div>

        <aside class="border border-line bg-surface p-7">
          <p class="eyebrow">Rombongan</p>
          <h3 class="mt-4 font-display text-2xl leading-snug">Butuh lebih dari 50 pcs?</h3>
          <p class="mt-3 text-[0.85rem] leading-relaxed text-muted">
            Harga menyesuaikan jumlah dan kartu bisa dicetak dengan nama jamaah. Kirim daftar kebutuhan Anda, kami balas dengan penawaran di hari yang sama.
          </p>
          <AppButton :href="whatsappHref('Halo ArafahGift, saya mau tanya tentang produk ini.')" variant="outline" class="mt-6" block target="_blank" rel="noopener">Konsultasi via WhatsApp</AppButton>
        </aside>
      </div>
    </section>

    <!-- Related -->
    <section class="shell pt-12">
      <h2 class="text-[1.9rem] sm:text-[2.3rem]">Sering dibeli bersama</h2>
      <div class="mt-10 grid grid-cols-2 gap-x-5 gap-y-12 lg:grid-cols-4 lg:gap-x-8">
        <ProductCard v-for="(p, i) in related" :key="p.id" :product="p" :index="i + 1" />
      </div>
    </section>

    <!-- Sticky bar mobile -->
    <!-- Sticky mobile buy bar -->
    <div class="sticky bottom-0 z-40 mt-10 border-t border-forest-soft/30 bg-forest-deep lg:hidden" style="padding-bottom: env(safe-area-inset-bottom, 0px)">
      <div class="flex items-center gap-3 px-4 py-3">
        <div class="min-w-0 flex-1">
          <p class="truncate text-[0.7rem] text-ivory/50">{{ product.name }}</p>
          <p class="font-display text-[1.25rem] leading-none text-ivory">{{ formatIDR(product.price) }}</p>
        </div>
        <button
          class="grid h-11 w-11 place-items-center border border-forest-soft/40 transition hover:border-gold"
          :aria-label="wishlist.has(product.id) ? 'Hapus dari wishlist' : 'Simpan'"
          @click="wishlist.toggle(product.id)"
        >
          <Heart class="h-[18px] w-[18px]" :class="wishlist.has(product.id) ? 'fill-gold text-gold' : 'text-ivory/60'" :stroke-width="1.5" />
        </button>
        <AppButton variant="gold" :disabled="soldOut" @click="cart.add(product, qty)">
          {{ soldOut ? 'Habis' : 'Tambah ke keranjang' }}
        </AppButton>
      </div>
    </div>
  </div>

  <div v-else class="shell py-24">
    <EmptyState title="Produk tidak ditemukan" body="Tautan mungkin sudah berubah. Lihat koleksi kami untuk menemukan yang serupa.">
      <AppButton to="/koleksi">Ke koleksi</AppButton>
    </EmptyState>
  </div>
</template>
