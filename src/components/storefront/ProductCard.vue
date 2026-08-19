<script setup>
import { computed } from 'vue'
import { Eye, Heart, ShoppingBag } from 'lucide-vue-next'
import ProductArt from '@/components/art/ProductArt.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppRating from '@/components/ui/AppRating.vue'
import { formatIDR } from '@/composables/useFormat'
import { useCart } from '@/composables/useCart'
import { useWishlist } from '@/composables/useWishlist'

const props = defineProps({
  product: { type: Object, required: true },
  index: { type: Number, default: 0 },
})
const emit = defineEmits(['quickview'])

const { add } = useCart()
const wishlist = useWishlist()

const soldOut = computed(() => props.product.stock === 0)
const discount = computed(() =>
  props.product.comparePrice
    ? Math.round((1 - props.product.price / props.product.comparePrice) * 100)
    : 0,
)
</script>

<template>
  <article class="group relative flex flex-col">
    <div class="relative">
      <router-link
        :to="`/produk/${product.slug}`"
        class="arch block aspect-[4/5] overflow-hidden border border-line bg-surface"
        :aria-label="product.name"
      >
        <div class="h-full w-full transition-transform duration-[900ms] ease-calm group-hover:scale-[1.04]">
          <img v-if="product.image" :src="product.image" :alt="product.name" class="h-full w-full object-cover" />
          <ProductArt v-else :art="product.art" :tone="index" />
        </div>
      </router-link>

      <div class="pointer-events-none absolute left-2 top-2 sm:left-3 sm:top-4 flex flex-col gap-1">
        <AppBadge v-if="product.badge && !soldOut">{{ product.badge }}</AppBadge>
        <AppBadge v-if="discount && !soldOut" tone="olive">−{{ discount }}%</AppBadge>
        <AppBadge v-if="soldOut" tone="muted">Stok habis</AppBadge>
      </div>

      <button
        class="absolute right-2 top-2 sm:right-3 sm:top-4 grid h-7 w-7 sm:h-9 sm:w-9 place-items-center rounded-full border border-line bg-surface/90 backdrop-blur transition hover:border-gold"
        :aria-label="wishlist.has(product.id) ? 'Hapus dari wishlist' : 'Simpan ke wishlist'"
        :aria-pressed="wishlist.has(product.id)"
        @click="wishlist.toggle(product.id)"
      >
        <Heart
          class="h-3.5 w-3.5 sm:h-4 sm:w-4 transition"
          :class="wishlist.has(product.id) ? 'fill-gold text-gold' : 'text-forest'"
          :stroke-width="1.5"
        />
      </button>

      <!-- Aksi cepat: muncul saat hover di desktop, selalu terlihat di mobile -->
      <div
        class="absolute inset-x-3 bottom-3 flex gap-2 transition-all duration-300 ease-calm md:translate-y-2 md:opacity-0 md:group-hover:translate-y-0 md:group-hover:opacity-100"
      >
        <button
          class="flex flex-1 items-center justify-center gap-2 border border-forest bg-forest/95 px-3 py-2.5 text-[0.75rem] font-medium tracking-wide text-ivory backdrop-blur transition hover:bg-forest-soft disabled:opacity-50"
          :disabled="soldOut"
          @click="add(product)"
        >
          <ShoppingBag class="h-3.5 w-3.5" />
          {{ soldOut ? 'Habis' : 'Tambah' }}
        </button>
        <button
          class="hidden h-[38px] w-10 place-items-center border border-line bg-surface/95 text-forest backdrop-blur transition hover:border-forest md:grid"
          aria-label="Lihat cepat"
          @click="emit('quickview', product)"
        >
          <Eye class="h-4 w-4" :stroke-width="1.5" />
        </button>
      </div>
    </div>

    <div class="flex flex-1 flex-col pt-3 sm:pt-4">
      <p class="text-[0.6rem] uppercase tracking-[0.14em] text-olive sm:text-[0.68rem] sm:tracking-[0.16em]">{{ product.category }}</p>
      <h3 class="mt-1 sm:mt-2 font-display text-[0.95rem] sm:text-[1.25rem] leading-snug">
        <router-link :to="`/produk/${product.slug}`" class="link-underline">
          {{ product.name }}
        </router-link>
      </h3>
      <div class="mt-1 sm:mt-2"><AppRating :value="product.rating" :count="product.reviews" /></div>
      <div class="mt-2 sm:mt-3 flex flex-wrap items-baseline gap-1.5 sm:gap-2">
        <span class="text-[0.88rem] sm:text-[0.95rem] font-semibold text-forest">{{ formatIDR(product.price) }}</span>
        <span v-if="product.comparePrice" class="text-[0.72rem] sm:text-[0.8rem] text-muted line-through">
          {{ formatIDR(product.comparePrice) }}
        </span>
      </div>
    </div>
  </article>
</template>
