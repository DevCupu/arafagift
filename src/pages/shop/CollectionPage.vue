<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { SlidersHorizontal, X } from 'lucide-vue-next'
import ProductCard from '@/components/storefront/ProductCard.vue'
import ProductCardSkeleton from '@/components/ui/ProductCardSkeleton.vue'
import QuickView from '@/components/storefront/QuickView.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { categories, occasions, products } from '@/data/catalog'

const route = useRoute()
const quickview = ref(null)
const loading = ref(false)
const filtersOpen = ref(false)

const activeCategory = computed(() => route.params.category ?? 'semua')
const activeOccasion = ref(route.query.untuk ?? null)
const sort = ref('populer')
const priceBand = ref('semua')

const bands = [
  { id: 'semua', label: 'Semua harga' },
  { id: 'low', label: 'Di bawah Rp 150 rb', test: (p) => p.price < 150000 },
  { id: 'mid', label: 'Rp 150 rb – 500 rb', test: (p) => p.price >= 150000 && p.price <= 500000 },
  { id: 'high', label: 'Di atas Rp 500 rb', test: (p) => p.price > 500000 },
]

const sorts = [
  { id: 'populer', label: 'Paling populer' },
  { id: 'baru', label: 'Terbaru' },
  { id: 'murah', label: 'Harga terendah' },
  { id: 'mahal', label: 'Harga tertinggi' },
]

const categoryMeta = computed(() => categories.find((c) => c.slug === activeCategory.value))

const list = computed(() => {
  let out = products.filter((p) => p.status === 'active')
  if (activeCategory.value !== 'semua') out = out.filter((p) => p.categorySlug === activeCategory.value)
  if (activeOccasion.value) out = out.filter((p) => p.occasions.includes(activeOccasion.value))
  const band = bands.find((b) => b.id === priceBand.value)
  if (band?.test) out = out.filter(band.test)

  const by = {
    populer: (a, b) => b.reviews - a.reviews,
    baru: (a, b) => b.id - a.id,
    murah: (a, b) => a.price - b.price,
    mahal: (a, b) => b.price - a.price,
  }
  return [...out].sort(by[sort.value])
})

const resetFilters = () => {
  activeOccasion.value = null
  priceBand.value = 'semua'
}

// Skeleton singkat setiap filter berubah — meniru fetch ke API Laravel.
watch([activeCategory, activeOccasion, priceBand, sort], () => {
  loading.value = true
  setTimeout(() => (loading.value = false), 380)
})
watch(() => route.query.untuk, (v) => { activeOccasion.value = v ?? null })
</script>

<template>
  <div>
    <!-- Header koleksi -->
    <header class="border-b border-forest-soft/30 bg-forest-deep">
      <div class="shell py-14 sm:py-20">
        <nav class="flex items-center gap-2 text-[0.72rem] uppercase tracking-[0.14em] text-ivory/45" aria-label="Breadcrumb">
          <router-link to="/" class="transition hover:text-ivory">Home</router-link>
          <span class="text-gold">/</span>
          <router-link to="/koleksi" class="transition hover:text-ivory">Koleksi</router-link>
          <template v-if="categoryMeta">
            <span class="text-gold">/</span><span class="text-ivory/75">{{ categoryMeta.name }}</span>
          </template>
        </nav>
        <h1 class="mt-6 text-[2.4rem] leading-none text-ivory sm:text-[3.2rem]">
          {{ categoryMeta ? categoryMeta.name : 'Seluruh Koleksi' }}
        </h1>
        <p class="mt-4 max-w-lg text-[0.95rem] leading-relaxed text-ivory/60">
          {{ categoryMeta ? categoryMeta.tagline + ' — dipilih dan diuji sendiri sebelum masuk katalog.'
            : 'Semua yang kami bawa pulang: kurma, sajadah, tasbih, gift set, dan souvenir rombongan.' }}
        </p>
      </div>
    </header>

    <div class="shell grid gap-10 py-12 lg:grid-cols-[15rem_1fr] lg:gap-14">
      <!-- Filter rail -->
      <aside
        class="lg:sticky lg:top-28 lg:h-fit"
        :class="filtersOpen ? 'fixed inset-0 z-[60] overflow-y-auto bg-ivory p-6' : 'hidden lg:block'"
      >
        <div class="mb-6 flex items-center justify-between lg:hidden">
          <h2 class="font-display text-2xl">Filter</h2>
          <button class="grid h-9 w-9 place-items-center" aria-label="Tutup filter" @click="filtersOpen = false">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="space-y-8">
          <div>
            <h3 class="text-eyebrow font-medium uppercase text-gold">Kategori</h3>
            <ul class="mt-4 space-y-2.5">
              <li>
                <router-link to="/koleksi" class="text-[0.85rem] transition" :class="activeCategory === 'semua' ? 'text-forest' : 'text-muted hover:text-forest'">
                  Semua produk
                </router-link>
              </li>
              <li v-for="c in categories" :key="c.slug">
                <router-link
                  :to="`/koleksi/${c.slug}`"
                  class="flex items-center justify-between text-[0.85rem] transition"
                  :class="activeCategory === c.slug ? 'text-forest' : 'text-muted hover:text-forest'"
                >
                  {{ c.name }}<span class="text-[0.7rem] text-muted/60">{{ c.count }}</span>
                </router-link>
              </li>
            </ul>
          </div>

          <div>
            <h3 class="text-eyebrow font-medium uppercase text-gold">Untuk siapa</h3>
            <div class="mt-4 flex flex-wrap gap-2">
              <button
                v-for="o in occasions" :key="o.slug"
                class="border px-3 py-1.5 text-[0.75rem] transition"
                :class="activeOccasion === o.slug ? 'border-forest bg-forest text-ivory' : 'border-line text-muted hover:border-olive/60'"
                @click="activeOccasion = activeOccasion === o.slug ? null : o.slug"
              >{{ o.title.replace('Untuk ', '') }}</button>
            </div>
          </div>

          <div>
            <h3 class="text-eyebrow font-medium uppercase text-gold">Harga</h3>
            <ul class="mt-4 space-y-2.5">
              <li v-for="b in bands" :key="b.id">
                <label class="flex cursor-pointer items-center gap-2.5 text-[0.85rem] text-muted transition hover:text-forest">
                  <input v-model="priceBand" type="radio" :value="b.id" class="h-3.5 w-3.5 accent-[rgb(var(--c-forest))]" />
                  {{ b.label }}
                </label>
              </li>
            </ul>
          </div>

          <button class="text-[0.8rem] text-muted underline underline-offset-4 transition hover:text-forest" @click="resetFilters">
            Atur ulang filter
          </button>
        </div>

        <AppButton v-if="filtersOpen" block class="mt-8 lg:hidden" @click="filtersOpen = false">
          Tampilkan {{ list.length }} produk
        </AppButton>
      </aside>

      <!-- Grid -->
      <section>
        <div class="flex items-center justify-between gap-4 border-b border-line pb-4">
          <p class="text-[0.8rem] text-muted">
            <span class="text-forest">{{ list.length }}</span> produk
          </p>
          <div class="flex items-center gap-3">
            <button class="flex items-center gap-2 border border-line px-3 py-2 text-[0.78rem] text-forest lg:hidden" @click="filtersOpen = true">
              <SlidersHorizontal class="h-3.5 w-3.5" /> Filter
            </button>
            <label class="sr-only" for="sort">Urutkan</label>
            <select id="sort" v-model="sort" class="border border-line bg-surface px-3 py-2 text-[0.78rem] text-forest focus:border-olive focus:outline-none">
              <option v-for="s in sorts" :key="s.id" :value="s.id">{{ s.label }}</option>
            </select>
          </div>
        </div>

        <div v-if="loading" class="mt-10 grid grid-cols-2 gap-x-5 gap-y-12 lg:grid-cols-3 lg:gap-x-8">
          <ProductCardSkeleton v-for="i in 6" :key="i" />
        </div>

        <div v-else-if="list.length" class="mt-10 grid grid-cols-2 gap-x-5 gap-y-12 lg:grid-cols-3 lg:gap-x-8">
          <ProductCard
            v-for="(p, i) in list" :key="p.id" :product="p" :index="i"
            @quickview="quickview = $event"
          />
        </div>

        <EmptyState
          v-else class="mt-10"
          title="Tidak ada produk pada filter ini"
          body="Coba lepas satu filter, atau lihat seluruh koleksi kami."
        >
          <AppButton variant="outline" @click="resetFilters">Atur ulang filter</AppButton>
        </EmptyState>
      </section>
    </div>

    <QuickView :product="quickview" @close="quickview = null" />
  </div>
</template>
