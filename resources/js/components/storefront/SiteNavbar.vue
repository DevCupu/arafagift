<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Menu, Search, ShoppingBag, User, X } from 'lucide-vue-next'
import BrandLogo from '@/components/storefront/BrandLogo.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import { products } from '@/data/catalog'
import { formatIDR } from '@/composables/useFormat'
import { useCart } from '@/composables/useCart'

const page = usePage()
const { count, openDrawer, pulse } = useCart()

const links = [
  { label: 'Home', to: '/' },
  { label: 'Koleksi', to: '/koleksi' },
  { label: 'Gift Set', to: '/koleksi/gift-set' },
  { label: 'Tentang Kami', to: '/tentang' },
  { label: 'FAQ', to: '/faq' },
]

const menuOpen = ref(false)
const searchOpen = ref(false)
const query = ref('')
const scrolled = ref(false)

const results = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return []
  return products
    .filter((p) => `${p.name} ${p.category} ${p.short}`.toLowerCase().includes(q))
    .slice(0, 5)
})

const onScroll = () => { scrolled.value = window.scrollY > 8 }
onMounted(() => { onScroll(); window.addEventListener('scroll', onScroll, { passive: true }) })
onUnmounted(() => window.removeEventListener('scroll', onScroll))

watch(() => page.url, () => { menuOpen.value = false; searchOpen.value = false })
watch([menuOpen, searchOpen], ([m, s]) => {
  document.body.style.overflow = m || s ? 'hidden' : ''
})
</script>

<template>
  <header
    class="sticky top-0 z-[100] border-b transition-colors duration-300 bg-forest-deep"
    :class="scrolled ? 'border-forest-soft/40' : 'border-transparent'"
    style="transform: translate3d(0, 0, 0); backface-visibility: hidden;"
  >
    <nav class="shell flex h-16 items-center justify-between gap-6 md:h-[72px]" aria-label="Utama">
      <button
        class="-ml-2 grid h-10 w-10 place-items-center text-ivory/85 hover:text-ivory md:hidden"
        :aria-expanded="menuOpen"
        aria-label="Buka menu"
        @click="menuOpen = true"
      >
        <Menu class="h-5 w-5" :stroke-width="1.5" />
      </button>

      <Link href="/" class="md:mr-8" aria-label="ArafahGift.id — beranda">
        <BrandLogo tone="ivory" />
      </Link>

      <ul class="hidden flex-1 items-center gap-8 md:flex">
        <li v-for="link in links" :key="link.to">
          <Link
            :href="link.to"
            class="relative text-[0.82rem] tracking-wide text-ivory/75 transition-colors hover:text-ivory"
            :class="page.url === link.to ? 'text-ivory' : ''"
          >
            {{ link.label }}
            <span v-if="page.url === link.to" class="absolute -bottom-1 left-0 right-0 h-px bg-gold" />
          </Link>
        </li>
      </ul>

      <div class="flex items-center gap-1">
        <button class="grid h-10 w-10 place-items-center text-ivory/75 transition hover:text-ivory" aria-label="Cari produk" @click="searchOpen = true">
          <Search class="h-[18px] w-[18px]" :stroke-width="1.5" />
        </button>
        <Link href="/akun" class="hidden h-10 w-10 place-items-center text-ivory/75 transition hover:text-ivory sm:grid" aria-label="Akun saya">
          <User class="h-[18px] w-[18px]" :stroke-width="1.5" />
        </Link>
        <button
          class="relative -mr-2 grid h-10 w-10 place-items-center text-ivory/75 transition hover:text-ivory"
          :aria-label="`Keranjang, ${count} item`"
          @click="openDrawer"
        >
          <ShoppingBag class="h-[18px] w-[18px]" :stroke-width="1.5" />
          <span
            v-if="count"
            :key="pulse"
            class="absolute right-0 top-1.5 grid h-[18px] min-w-[18px] animate-[pop_.35s_cubic-bezier(.22,1,.36,1)] place-items-center rounded-full bg-gold px-1 text-[0.62rem] font-bold text-forest-deep"
          >{{ count }}</span>
        </button>
      </div>
    </nav>

    <Teleport to="body">
      <!-- Menu mobile -->
      <Transition
        enter-active-class="transition duration-300 ease-calm" enter-from-class="-translate-x-full"
        leave-active-class="transition duration-[250ms] ease-calm" leave-to-class="-translate-x-full"
      >
        <div v-if="menuOpen" class="fixed inset-y-0 left-0 z-[120] flex w-[86%] max-w-sm flex-col bg-forest-deep md:hidden">
          <!-- Header drawer -->
          <div class="flex h-16 items-center justify-between border-b border-forest-soft/30 px-5">
            <BrandLogo tone="ivory" size="sm" />
            <button class="grid h-10 w-10 place-items-center text-ivory/70 transition hover:text-ivory" aria-label="Tutup menu" @click="menuOpen = false">
              <X class="h-5 w-5" :stroke-width="1.5" />
            </button>
          </div>
          <!-- Nav links -->
          <ul class="flex-1 overflow-y-auto px-5 pt-2">
            <li v-for="link in links" :key="link.to" class="border-b border-forest-soft/20">
              <Link
                :href="link.to"
                class="flex items-center justify-between py-4 font-display text-[1.65rem] text-ivory/85 transition-colors hover:text-ivory"
                :class="page.url === link.to ? 'text-ivory' : ''"
              >
                {{ link.label }}
                <span v-if="page.url === link.to" class="h-1.5 w-1.5 rotate-45 bg-gold" />
              </Link>
            </li>
          </ul>
          <!-- Drawer footer -->
          <div class="border-t border-forest-soft/20 px-5 py-6 space-y-4">
            <Link href="/akun" class="flex items-center gap-2.5 text-[0.9rem] text-ivory/75 transition hover:text-ivory">
              <User class="h-4 w-4 text-gold" :stroke-width="1.5" /> Akun saya
            </Link>
            <a
              href="https://wa.me/6281234567890" target="_blank" rel="noopener"
              class="flex items-center gap-2.5 text-[0.9rem] text-ivory/75 transition hover:text-ivory"
            >
              <span class="h-4 w-4 text-gold flex-none">
                <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.126 1.533 5.856L.057 23.786a.5.5 0 0 0 .612.638l6.08-1.592A11.946 11.946 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 0 1-5.002-1.37l-.36-.213-3.713.973.988-3.607-.234-.372A9.818 9.818 0 1 1 12 21.818z"/></svg>
              </span>
              Chat WhatsApp
            </a>
          </div>
        </div>
      </Transition>
      <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
        <div v-if="menuOpen" class="fixed inset-0 z-[115] bg-forest-deep/60 backdrop-blur-sm md:hidden" @click="menuOpen = false" />
      </Transition>
    </Teleport>

    <Teleport to="body">
      <!-- Search overlay -->
      <Transition
        enter-active-class="transition duration-[250ms] ease-calm" enter-from-class="-translate-y-4 opacity-0"
        leave-active-class="transition duration-200" leave-to-class="-translate-y-2 opacity-0"
      >
        <div v-if="searchOpen" class="fixed inset-x-0 top-0 z-[122] border-b border-line bg-surface shadow-soft">
          <div class="shell py-5">
            <div class="flex items-center gap-3 border-b border-line pb-3">
              <Search class="h-5 w-5 flex-none text-gold" :stroke-width="1.5" />
              <input
                v-model="query" autofocus type="search"
                placeholder="Cari kurma, sajadah, gift set…"
                class="w-full bg-transparent font-display text-2xl text-forest placeholder:text-muted/50 focus:outline-none"
              />
              <button class="grid h-9 w-9 flex-none place-items-center text-forest" aria-label="Tutup pencarian" @click="searchOpen = false">
                <X class="h-4 w-4" />
              </button>
            </div>
            <ul v-if="results.length" class="mt-4 space-y-1">
              <li v-for="p in results" :key="p.id">
                <Link :href="`/produk/${p.slug}`" class="flex items-center gap-4 px-2 py-2.5 transition hover:bg-ivory">
                  <span class="arch h-14 w-11 flex-none overflow-hidden border border-line"><ProductArt :art="p.art" :tone="p.id" /></span>
                  <span class="flex-1">
                    <span class="block text-[0.7rem] uppercase tracking-[0.14em] text-olive">{{ p.category }}</span>
                    <span class="block font-display text-lg text-forest">{{ p.name }}</span>
                  </span>
                  <span class="text-[0.83rem] text-forest">{{ formatIDR(p.price) }}</span>
                </Link>
              </li>
            </ul>
            <p v-else-if="query" class="mt-6 text-[0.85rem] text-muted">
              Tidak ada produk yang cocok dengan “{{ query }}”. Coba kata yang lebih umum, misalnya “kurma”.
            </p>
            <p v-else class="mt-6 text-[0.78rem] uppercase tracking-[0.14em] text-muted">
              Populer: kurma ajwa · gift set · sajadah travel
            </p>
          </div>
        </div>
      </Transition>
      <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
        <div v-if="searchOpen" class="fixed inset-0 z-[114] bg-forest-deep/30" @click="searchOpen = false" />
      </Transition>
    </Teleport>
  </header>
</template>

<style>
@keyframes pop {
  0% { transform: scale(0.6); }
  60% { transform: scale(1.15); }
  100% { transform: scale(1); }
}
</style>
