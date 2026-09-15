<script setup>
import { computed, ref } from 'vue'
import { Eye, MessageCircle, PackageCheck, Sparkles, Star } from 'lucide-vue-next'
import AppModal from '@/components/ui/AppModal.vue'
import AppRating from '@/components/ui/AppRating.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import { formatIDR } from '@/composables/useFormat'
import { useStore } from '@/composables/useStore'

const props = defineProps({
  content: { type: Object, required: true },
  products: { type: Object, default: () => ({}) },
})

const { whatsappHref } = useStore()
const selected = ref(null)

const normalizeIncludes = (includes = []) =>
  includes
    .map((item) => (typeof item === 'string' ? item : item?.value))
    .filter(Boolean)

const normalizeDetails = (details = []) =>
  details
    .map((item) => {
      if (Array.isArray(item)) return { label: item[0], value: item[1] }
      return { label: item?.label, value: item?.value }
    })
    .filter((item) => item.label && item.value)

const normalizeProduct = (product, source = 'catalog') => ({
  id: product.id ?? product.name,
  source,
  name: product.name,
  slug: product.slug,
  category: product.category ?? 'Pilihan Arafah',
  image: product.image,
  art: product.art ?? 'giftset',
  price: Number(product.price || 0),
  comparePrice: product.comparePrice ? Number(product.comparePrice) : null,
  rating: product.rating ? Number(product.rating) : null,
  reviews: product.reviews ? Number(product.reviews) : null,
  badge: product.badge,
  stock: product.stock,
  description: product.description || product.short || 'Produk pilihan ArafahGift untuk hadiah pulang umrah yang rapi, pantas, dan siap diberikan.',
  includes: normalizeIncludes(product.includes),
  details: normalizeDetails(product.details),
})

const items = computed(() => [
  ...(props.content.productIds ?? []).map((id) => props.products[id]).filter(Boolean).map((item) => normalizeProduct(item)),
  ...(props.content.customItems ?? []).filter((item) => item.name).map((item) => normalizeProduct(item, 'manual')),
])

const discount = (item) =>
  item.comparePrice && item.price
    ? Math.round((1 - item.price / item.comparePrice) * 100)
    : 0

const itemHref = (item, action = 'checkout') => {
  const label = action === 'detail' ? 'minta detail' : 'checkout'
  return whatsappHref(`Halo ArafahGift, saya ingin ${label} "${item.name}".`)
}

const openDetail = (item) => {
  selected.value = item
}
</script>

<template>
  <section v-if="items.length" class="relative overflow-hidden py-16 sm:py-20">
    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold/50 to-transparent" />

    <div class="shell">
      <div class="max-w-2xl">
        <div v-if="content.eyebrow" class="flex items-center gap-3">
          <span class="h-px w-8 bg-gold" />
          <p class="text-[0.68rem] font-semibold uppercase tracking-[0.25em] text-gold">{{ content.eyebrow }}</p>
        </div>
        <h2 v-if="content.title" class="mt-3 text-[1.9rem] sm:text-[2.6rem]">{{ content.title }}</h2>
        <p v-if="content.intro" class="mt-4 max-w-[58ch] text-[0.95rem] leading-7 text-muted">
          {{ content.intro }}
        </p>
      </div>

      <div class="mt-9 grid gap-4 lg:grid-cols-[1.18fr_1fr]" v-reveal>
        <article
          v-if="items[0]"
          class="reveal-child group grid overflow-hidden rounded-2xl border border-forest/15 bg-surface shadow-lift sm:grid-cols-[0.94fr_1fr]"
          style="--reveal-delay: 0ms"
        >
          <button
            type="button"
            class="relative block min-h-[330px] overflow-hidden bg-sand text-left sm:min-h-[440px]"
            :aria-label="`Lihat detail ${items[0].name}`"
            @click="openDetail(items[0])"
          >
            <img
              v-if="items[0].image"
              :src="items[0].image"
              :alt="items[0].name"
              loading="lazy"
              class="h-full w-full object-cover transition duration-[900ms] ease-calm group-hover:scale-[1.04]"
            />
            <ProductArt v-else :art="items[0].art" :tone="0" />
            <div class="absolute inset-0 bg-gradient-to-t from-forest-deep/60 via-transparent to-transparent" />
            <div class="absolute left-4 top-4 flex flex-wrap gap-2">
              <span
                v-if="items[0].badge"
                class="rounded-full bg-surface/95 px-3 py-1 text-[0.7rem] font-semibold text-forest shadow-soft"
              >
                {{ items[0].badge }}
              </span>
              <span
                v-if="discount(items[0])"
                class="rounded-full bg-gold px-3 py-1 text-[0.7rem] font-semibold text-forest-deep shadow-soft"
              >
                -{{ discount(items[0]) }}%
              </span>
            </div>
          </button>

          <div class="flex flex-col justify-between p-5 sm:p-7">
            <div>
              <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-olive">{{ items[0].category }}</p>
              <h3 class="mt-3 text-[1.8rem] leading-tight sm:text-[2.35rem]">{{ items[0].name }}</h3>
              <p class="mt-4 line-clamp-4 text-[0.95rem] leading-7 text-muted">{{ items[0].description }}</p>

              <div class="mt-5 flex flex-wrap items-center gap-3">
                <AppRating v-if="items[0].rating" :value="items[0].rating" :count="items[0].reviews" />
                <span v-if="items[0].stock === 0" class="text-[0.78rem] font-semibold text-danger">Stok habis</span>
              </div>
            </div>

            <div class="mt-7">
              <div class="flex flex-wrap items-baseline gap-2">
                <span class="text-[1.35rem] font-semibold text-forest">{{ formatIDR(items[0].price) }}</span>
                <span v-if="items[0].comparePrice" class="text-[0.9rem] text-muted line-through">
                  {{ formatIDR(items[0].comparePrice) }}
                </span>
              </div>

              <div class="mt-5 grid gap-2 sm:grid-cols-2">
                <button
                  type="button"
                  class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-forest bg-forest px-4 py-2.5 text-[0.82rem] font-semibold text-ivory transition hover:bg-forest-deep active:translate-y-px"
                  @click="openDetail(items[0])"
                >
                  <Eye class="h-4 w-4" :stroke-width="1.6" />
                  Detail
                </button>
                <a
                  :href="itemHref(items[0])"
                  class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-gold bg-gold px-4 py-2.5 text-[0.82rem] font-semibold text-forest-deep transition hover:bg-gold-soft active:translate-y-px"
                >
                  <MessageCircle class="h-4 w-4" :stroke-width="1.6" />
                  Checkout WA
                </a>
              </div>
            </div>
          </div>
        </article>

        <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
          <article
            v-for="(item, i) in items.slice(1)"
            :key="item.id"
            class="reveal-child group grid grid-cols-[112px_1fr] gap-4 rounded-2xl border border-line bg-surface p-3 shadow-soft transition duration-300 ease-calm hover:-translate-y-0.5 hover:border-gold/70 hover:shadow-lift sm:grid-cols-1 lg:grid-cols-[132px_1fr]"
            :style="{ '--reveal-delay': `${(i + 1) * 95}ms` }"
          >
            <button
              type="button"
              class="relative aspect-[4/5] overflow-hidden rounded-xl bg-sand"
              :aria-label="`Lihat detail ${item.name}`"
              @click="openDetail(item)"
            >
              <img
                v-if="item.image"
                :src="item.image"
                :alt="item.name"
                loading="lazy"
                class="h-full w-full object-cover transition duration-[900ms] ease-calm group-hover:scale-[1.04]"
              />
              <ProductArt v-else :art="item.art" :tone="i + 1" />
              <span
                v-if="item.badge || discount(item)"
                class="absolute left-2 top-2 rounded-full bg-surface/95 px-2.5 py-1 text-[0.66rem] font-semibold text-forest shadow-soft"
              >
                {{ item.badge || `-${discount(item)}%` }}
              </span>
            </button>

            <div class="flex min-w-0 flex-col justify-between py-1">
              <div>
                <p class="text-[0.66rem] font-semibold uppercase tracking-[0.16em] text-olive">{{ item.category }}</p>
                <h3 class="mt-1.5 text-[1rem] leading-snug sm:text-[1.12rem]">{{ item.name }}</h3>
                <div class="mt-2 flex items-center gap-1.5 text-[0.78rem] text-muted">
                  <Star class="h-3.5 w-3.5 fill-gold text-gold" :stroke-width="1.6" />
                  <span>{{ item.rating || '4.8' }}</span>
                  <span v-if="item.reviews">({{ item.reviews }})</span>
                </div>
              </div>

              <div class="mt-3">
                <div class="flex flex-wrap items-baseline gap-1.5">
                  <span class="font-semibold text-forest">{{ formatIDR(item.price) }}</span>
                  <span v-if="item.comparePrice" class="text-[0.74rem] text-muted line-through">
                    {{ formatIDR(item.comparePrice) }}
                  </span>
                </div>
                <div class="mt-3 flex gap-2">
                  <button
                    type="button"
                    class="grid h-10 w-10 place-items-center rounded-full border border-line text-forest transition hover:border-gold hover:bg-gold/10 active:translate-y-px"
                    :aria-label="`Buka detail ${item.name}`"
                    @click="openDetail(item)"
                  >
                    <Eye class="h-4 w-4" :stroke-width="1.6" />
                  </button>
                  <a
                    :href="itemHref(item)"
                    class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-full bg-forest px-3 text-[0.76rem] font-semibold text-ivory transition hover:bg-forest-deep active:translate-y-px"
                  >
                    <MessageCircle class="h-3.5 w-3.5" :stroke-width="1.6" />
                    WA
                  </a>
                </div>
              </div>
            </div>
          </article>
        </div>
      </div>
    </div>

    <AppModal :open="Boolean(selected)" :label="selected?.name || 'Detail produk'" @close="selected = null">
      <div v-if="selected" class="grid bg-surface sm:grid-cols-[0.92fr_1.08fr]">
        <div class="relative min-h-[330px] overflow-hidden bg-sand sm:min-h-[560px]">
          <img
            v-if="selected.image"
            :src="selected.image"
            :alt="selected.name"
            class="h-full w-full object-cover"
          />
          <ProductArt v-else :art="selected.art" :tone="0" />
          <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-forest-deep/76 via-forest-deep/18 to-transparent" />
          <div class="absolute bottom-5 left-5 right-5">
            <div class="inline-flex items-center rounded-full bg-gold px-4 py-1.5 text-[0.78rem] font-semibold text-forest-deep shadow-soft">
              {{ selected.source === 'manual' ? 'Paket custom' : 'Produk pilihan' }}
            </div>
            <div class="mt-3 rounded-2xl border border-ivory/16 bg-forest-deep/82 p-4 text-ivory shadow-lift backdrop-blur">
              <p class="text-[0.72rem] font-semibold uppercase tracking-[0.15em] text-gold">Mulai dari</p>
              <div class="mt-1 flex flex-wrap items-baseline gap-2">
                <span class="text-[1.45rem] font-semibold">{{ formatIDR(selected.price) }}</span>
                <span v-if="selected.comparePrice" class="text-[0.85rem] text-ivory/62 line-through">
                  {{ formatIDR(selected.comparePrice) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex max-h-[92vh] flex-col">
          <div class="border-b border-line px-5 py-5 pr-16 sm:px-8 sm:py-6">
            <div class="flex flex-wrap items-center gap-2">
              <span v-if="selected.badge" class="rounded-full bg-gold/18 px-3 py-1 text-[0.72rem] font-semibold text-forest">
                {{ selected.badge }}
              </span>
              <span class="rounded-full bg-forest/8 px-3 py-1 text-[0.72rem] font-semibold text-forest">
                {{ selected.category }}
              </span>
              <span v-if="discount(selected)" class="rounded-full bg-danger/10 px-3 py-1 text-[0.72rem] font-semibold text-danger">
                Hemat {{ discount(selected) }}%
              </span>
            </div>

            <h3 class="mt-4 text-[1.8rem] leading-tight text-forest sm:text-[2.25rem]">{{ selected.name }}</h3>

            <div class="mt-4 flex flex-wrap items-center gap-3">
              <AppRating v-if="selected.rating" :value="selected.rating" :count="selected.reviews" />
              <span v-if="selected.stock === 0" class="text-[0.8rem] font-semibold text-danger">Stok habis</span>
              <span v-else-if="selected.source === 'manual'" class="text-[0.8rem] font-semibold text-olive">Pre-order via admin sales</span>
            </div>
          </div>

          <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-8 sm:py-6">
            <p class="text-[0.96rem] leading-7 text-muted">{{ selected.description }}</p>

            <div v-if="selected.includes.length" class="mt-6 rounded-2xl border border-line bg-ivory/70 p-4">
              <div class="mb-3 flex items-center gap-2 font-display text-[1rem] text-forest">
                <PackageCheck class="h-4 w-4 text-gold" :stroke-width="1.7" />
                Isi pilihan
              </div>
              <div class="grid gap-2">
                <div v-for="item in selected.includes" :key="item" class="flex gap-2 rounded-xl bg-surface px-3 py-2 text-[0.88rem] text-muted">
                  <Sparkles class="mt-1 h-3.5 w-3.5 flex-none text-gold" :stroke-width="1.7" />
                  <span>{{ item }}</span>
                </div>
              </div>
            </div>

            <div v-if="selected.details.length" class="mt-4 grid gap-2 sm:grid-cols-2">
              <div
                v-for="detail in selected.details"
                :key="`${detail.label}-${detail.value}`"
                class="rounded-2xl border border-line bg-surface px-4 py-3 shadow-soft"
              >
                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.15em] text-olive">{{ detail.label }}</p>
                <p class="mt-1 text-[0.9rem] font-medium text-forest">{{ detail.value }}</p>
              </div>
            </div>
          </div>

          <div class="border-t border-line bg-surface px-5 py-4 sm:px-8">
            <a
              :href="itemHref(selected, 'detail')"
              class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-forest px-5 py-3 text-[0.9rem] font-semibold text-ivory shadow-soft transition hover:bg-forest-deep active:translate-y-px"
            >
              <MessageCircle class="h-4 w-4" :stroke-width="1.7" />
              Chat untuk checkout
            </a>
          </div>
        </div>
      </div>
    </AppModal>
  </section>
</template>
