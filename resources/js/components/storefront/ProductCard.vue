<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
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
  <article
    class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-line/80 bg-surface shadow-soft transition duration-300 ease-calm hover:-translate-y-1 hover:border-gold/60 hover:shadow-lift"
  >
    <div class="relative border-b border-line/70">
      <Link
        :href="`/produk/${product.slug}`"
        class="block aspect-square overflow-hidden bg-ivory/55"
        :aria-label="product.name"
      >
        <div
          v-if="product.image"
          class="flex h-full w-full items-center justify-center p-2.5 transition-transform duration-500 ease-calm group-hover:scale-[1.015] sm:p-4"
        >
          <img
            :src="product.image"
            :alt="product.name"
            loading="lazy"
            class="h-full w-full object-contain object-center"
          />
        </div>
        <div v-else class="h-full w-full">
          <ProductArt :art="product.art" :tone="index" />
        </div>
      </Link>

      <div class="pointer-events-none absolute left-2.5 top-2.5 flex flex-col gap-1 sm:left-3.5 sm:top-3.5">
        <AppBadge v-if="product.badge && !soldOut">{{ product.badge }}</AppBadge>
        <AppBadge v-if="discount && !soldOut" tone="olive">-{{ discount }}%</AppBadge>
        <AppBadge v-if="soldOut" tone="muted">Stok habis</AppBadge>
      </div>

      <button
        class="absolute right-2.5 top-2.5 grid h-8 w-8 place-items-center rounded-full border border-line bg-surface/95 text-forest shadow-soft backdrop-blur transition hover:border-gold hover:bg-ivory active:scale-[0.96] sm:right-3.5 sm:top-3.5 sm:h-9 sm:w-9"
        :aria-label="wishlist.has(product.id) ? 'Hapus dari wishlist' : 'Simpan ke wishlist'"
        :aria-pressed="wishlist.has(product.id)"
        @click="wishlist.toggle(product.id)"
      >
        <Heart
          class="h-3.5 w-3.5 transition sm:h-4 sm:w-4"
          :class="wishlist.has(product.id) ? 'fill-gold text-gold' : 'text-forest'"
          :stroke-width="1.5"
        />
      </button>
    </div>

    <div class="flex flex-1 flex-col p-3 sm:p-4">
      <p class="text-[0.6rem] uppercase tracking-[0.14em] text-olive sm:text-[0.68rem] sm:tracking-[0.16em]">{{ product.category }}</p>
      <h3 class="mt-1 min-h-[2.65rem] font-display text-[0.95rem] leading-snug sm:mt-2 sm:min-h-[3.35rem] sm:text-[1.2rem]">
        <Link :href="`/produk/${product.slug}`" class="link-underline">
          {{ product.name }}
        </Link>
      </h3>
      <div class="mt-1 sm:mt-2"><AppRating :value="product.rating" :count="product.reviews" /></div>
      <div class="mt-2 flex flex-wrap items-baseline gap-1.5 sm:mt-3 sm:gap-2">
        <span class="text-[0.88rem] font-semibold text-forest sm:text-[0.95rem]">{{ formatIDR(product.price) }}</span>
        <span v-if="product.comparePrice" class="text-[0.72rem] text-muted line-through sm:text-[0.8rem]">
          {{ formatIDR(product.comparePrice) }}
        </span>
      </div>

      <div class="mt-auto grid grid-cols-[minmax(0,1fr)_2.5rem] gap-2 border-t border-line/70 pt-3 sm:grid-cols-[minmax(0,1fr)_2.75rem]">
        <button
          class="flex h-10 min-w-0 items-center justify-center gap-1.5 rounded-lg border border-forest bg-forest px-2 text-[0.72rem] font-medium tracking-wide text-ivory transition hover:border-forest-soft hover:bg-forest-soft active:translate-y-px disabled:cursor-not-allowed disabled:opacity-50 sm:h-11 sm:gap-2 sm:text-[0.78rem]"
          :disabled="soldOut"
          @click="add(product)"
        >
          <ShoppingBag class="h-3.5 w-3.5 flex-none" :stroke-width="1.7" />
          <span class="truncate">{{ soldOut ? 'Habis' : 'Tambah' }}</span>
        </button>
        <button
          class="grid h-10 w-10 place-items-center rounded-lg border border-line bg-ivory/60 text-forest transition hover:border-forest hover:bg-ivory active:translate-y-px sm:h-11 sm:w-11"
          aria-label="Lihat cepat"
          @click="emit('quickview', product)"
        >
          <Eye class="h-4 w-4" :stroke-width="1.5" />
        </button>
      </div>
    </div>
  </article>
</template>
