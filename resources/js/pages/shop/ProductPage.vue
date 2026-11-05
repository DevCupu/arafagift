<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
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
import { track } from '@/composables/useAnalytics'
import { productWhatsAppMessage } from '@/composables/useProductWhatsApp'
import { useStore } from '@/composables/useStore'

const props = defineProps({
  product: { type: Object, default: null },
  related: { type: Array, default: () => [] },
  reviews: { type: Array, default: () => [] },
  canReview: { type: Boolean, default: false },
  myReview: { type: Object, default: null },
  seoHead: { type: Object, required: true },
})
const cart = useCart()
const wishlist = useWishlist()
const { store, whatsappHref } = useStore()

const qty = ref(1)

// ── Ulasan ──
const page = usePage()
const reviewForm = useForm({ rating: 0, title: '', body: '' })
const submitReview = () => {
  reviewForm.post(`/produk/${props.product.slug}/ulasan`, {
    preserveScroll: true,
    onSuccess: () => reviewForm.reset(),
  })
}
const tab = ref('deskripsi')
watch(() => props.product?.slug, () => {
  qty.value = 1
  tab.value = 'deskripsi'

  if (props.product) {
    track('view_item', {
      currency: 'IDR',
      value: props.product.price,
      items: [{
        item_id: props.product.id,
        item_name: props.product.name,
        item_category: props.product.category,
        price: props.product.price,
        quantity: 1,
      }],
    })
  }
}, { immediate: true })

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

</script>

<template>
  <div v-if="product">
    <!-- Hanya judul. Meta, canonical, OG, dan JSON-LD Produk sudah dirender
         server oleh ProductController::seoHead(), termasuk aggregateRating
         yang hanya muncul bila ada ulasan yang disetujui. -->
    <Head :title="seoHead.title" />
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
          <AppRating v-if="product.reviews" :value="product.rating" :count="product.reviews" size="md" />
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
                <AppRating v-if="product.reviews" :value="product.rating" class="mt-2" />
              </div>
              <p class="text-[0.83rem] text-muted">
                {{ product.reviews ? `Dari ${product.reviews} ulasan pembeli terverifikasi.` : 'Belum ada ulasan.' }}
              </p>
            </div>
            <ul v-if="reviews.length" class="mt-8 divide-y divide-line border-y border-line">
              <li v-for="r in reviews" :key="r.id" class="py-5">
                <AppRating :value="r.rating" />
                <p v-if="r.title" class="mt-2 font-display text-lg text-forest">{{ r.title }}</p>
                <p class="mt-1 text-[0.88rem] leading-relaxed text-muted">{{ r.body }}</p>
                <p class="mt-2 text-[0.72rem] text-muted">{{ r.author }} · pembeli terverifikasi</p>
              </li>
            </ul>
            <div v-else class="mt-8 border border-dashed border-line px-6 py-12 text-center">
              <p class="font-display text-xl text-forest">Belum ada ulasan</p>
              <p class="mt-2 text-[0.85rem] text-muted">Jadilah yang pertama berbagi cerita setelah pesanan Anda sampai.</p>
            </div>

            <p v-if="myReview" class="mt-6 border border-gold/40 bg-gold/[0.08] p-4 text-[0.82rem] text-forest">
              Ulasan Anda
              {{ myReview.status === 'approved' ? 'sudah tayang.' : myReview.status === 'rejected' ? 'tidak dapat ditayangkan.' : 'sedang kami tinjau.' }}
            </p>

            <form v-else-if="canReview" class="mt-8 space-y-4 border border-line bg-surface p-6" @submit.prevent="submitReview">
              <h3 class="font-display text-xl">Tulis ulasan</h3>
              <div class="flex gap-1" role="radiogroup" aria-label="Rating">
                <button
                  v-for="n in 5" :key="n" type="button" role="radio" :aria-checked="reviewForm.rating === n"
                  class="text-2xl leading-none transition" :class="n <= reviewForm.rating ? 'text-gold' : 'text-sand'"
                  :aria-label="`${n} bintang`" @click="reviewForm.rating = n"
                >★</button>
              </div>
              <p v-if="reviewForm.errors.rating" class="text-[0.72rem] text-danger">{{ reviewForm.errors.rating }}</p>
              <input v-model="reviewForm.title" class="field" maxlength="120" placeholder="Judul (opsional)" />
              <textarea v-model="reviewForm.body" class="field" rows="4" maxlength="2000" placeholder="Ceritakan pengalaman Anda" />
              <p v-if="reviewForm.errors.body" class="text-[0.72rem] text-danger">{{ reviewForm.errors.body }}</p>
              <p v-if="page.props.errors.review" class="text-[0.72rem] text-danger">{{ page.props.errors.review }}</p>
              <AppButton type="submit" :loading="reviewForm.processing">Kirim ulasan</AppButton>
            </form>
            <p v-else class="mt-6 text-[0.78rem] text-muted">Ulasan dapat ditulis oleh pembeli setelah pesanan selesai.</p>
          </div>
        </div>

        <aside class="border border-line bg-surface p-7">
          <p class="eyebrow">Rombongan</p>
          <h3 class="mt-4 font-display text-2xl leading-snug">Butuh lebih dari 50 pcs?</h3>
          <p class="mt-3 text-[0.85rem] leading-relaxed text-muted">
            Harga menyesuaikan jumlah dan kartu bisa dicetak dengan nama jamaah. Kirim daftar kebutuhan Anda, kami balas dengan penawaran di hari yang sama.
          </p>
          <AppButton :href="whatsappHref(productWhatsAppMessage(product))" variant="outline" class="mt-6" block target="_blank" rel="noopener">Pesan produk ini via WhatsApp</AppButton>
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
