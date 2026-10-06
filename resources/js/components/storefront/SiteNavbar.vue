<script setup>
import { onUnmounted, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Menu, MessageCircle, Search, ShoppingBag, User, X } from 'lucide-vue-next'
import BrandLogo from '@/components/storefront/BrandLogo.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import { formatIDR } from '@/composables/useFormat'
import { useCart } from '@/composables/useCart'
import { useStore } from '@/composables/useStore'

const page = usePage()
const { whatsappHref } = useStore()
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

const results = ref([])
watch(query, (q, _, onCleanup) => {
  const term = q.trim()
  if (term.length < 2) { results.value = []; return }

  const controller = new AbortController()
  const timer = window.setTimeout(async () => {
    try {
      const res = await fetch(`/pencarian?q=${encodeURIComponent(term)}`, { signal: controller.signal })
      results.value = res.ok ? await res.json() : []
    } catch (error) {
      if (error.name !== 'AbortError') results.value = []
    }
  }, 250)

  onCleanup(() => {
    window.clearTimeout(timer)
    controller.abort()
  })
})

const isActive = (to) => page.url.split('?')[0] === to
const closeOverlays = () => {
  menuOpen.value = false
  searchOpen.value = false
}

watch(() => page.url, closeOverlays)
watch([menuOpen, searchOpen], ([m, s]) => {
  document.body.style.overflow = m || s ? 'hidden' : ''
})
onUnmounted(() => { document.body.style.overflow = '' })
</script>

<template>
  <header
    class="sticky top-0 z-[100] border-b border-ivory/10 bg-forest-deep/95 shadow-[0_12px_36px_-28px_rgb(4_43_38/0.9)] backdrop-blur-xl"
    style="transform: translate3d(0, 0, 0); backface-visibility: hidden;"
  >
    <nav class="shell relative flex h-[4.25rem] items-center gap-3 lg:h-[4.75rem] lg:gap-6" aria-label="Utama">
      <button
        class="-ml-2 grid h-11 w-11 flex-none place-items-center rounded-lg text-ivory/80 transition hover:bg-ivory/10 hover:text-ivory active:scale-[0.98] lg:hidden"
        :aria-expanded="menuOpen"
        aria-controls="mobile-navigation"
        aria-label="Buka menu"
        @click="menuOpen = true"
      >
        <Menu class="h-5 w-5" :stroke-width="1.5" />
      </button>

      <Link href="/" class="absolute left-1/2 flex -translate-x-1/2 items-center lg:static lg:mr-3 lg:translate-x-0" aria-label="Arafagift.id, beranda">
        <BrandLogo tone="ivory" />
      </Link>

      <div class="hidden flex-1 justify-center lg:flex">
        <ul class="flex items-center gap-1" role="list">
          <li v-for="link in links" :key="link.to">
            <Link
              :href="link.to"
              class="relative inline-flex min-h-11 items-center whitespace-nowrap rounded-lg px-4 text-[0.82rem] font-medium tracking-[0.015em] transition-colors active:scale-[0.98]"
              :class="isActive(link.to) ? 'bg-ivory/[0.09] text-ivory' : 'text-ivory/70 hover:bg-ivory/[0.06] hover:text-ivory'"
              :aria-current="isActive(link.to) ? 'page' : undefined"
            >{{ link.label }}</Link>
          </li>
        </ul>
      </div>

      <div class="ml-auto flex items-center gap-0.5 lg:border-l lg:border-ivory/12 lg:pl-4">
        <button class="grid h-11 w-11 place-items-center rounded-lg text-ivory/70 transition hover:bg-ivory/10 hover:text-ivory active:scale-[0.98]" aria-label="Cari produk" @click="searchOpen = true">
          <Search class="h-[18px] w-[18px]" :stroke-width="1.5" />
        </button>
        <Link href="/akun" class="hidden h-11 w-11 place-items-center rounded-lg text-ivory/70 transition hover:bg-ivory/10 hover:text-ivory active:scale-[0.98] sm:grid" aria-label="Akun saya">
          <User class="h-[18px] w-[18px]" :stroke-width="1.5" />
        </Link>
        <button
          class="relative grid h-11 w-11 place-items-center rounded-lg text-ivory/70 transition hover:bg-ivory/10 hover:text-ivory active:scale-[0.98]"
          :aria-label="`Keranjang, ${count} item`"
          @click="openDrawer"
        >
          <ShoppingBag class="h-[18px] w-[18px]" :stroke-width="1.5" />
          <span
            v-if="count"
            :key="pulse"
            class="absolute right-0 top-0.5 grid h-[18px] min-w-[18px] animate-[pop_.35s_cubic-bezier(.22,1,.36,1)] place-items-center rounded-full bg-gold px-1 text-[0.62rem] font-bold text-forest-deep ring-2 ring-forest-deep"
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
        <div
          v-if="menuOpen"
          id="mobile-navigation"
          class="fixed inset-y-0 left-0 z-[120] flex w-[88%] max-w-sm flex-col border-r border-ivory/10 bg-forest-deep shadow-[24px_0_60px_-32px_rgb(4_43_38/0.95)] lg:hidden"
          role="dialog"
          aria-modal="true"
          aria-label="Menu navigasi"
          @keydown.esc="menuOpen = false"
        >
          <!-- Header drawer -->
          <div class="flex h-[4.25rem] items-center justify-between border-b border-ivory/10 px-5">
            <BrandLogo tone="ivory" size="sm" />
            <button class="grid h-11 w-11 place-items-center rounded-lg text-ivory/70 transition hover:bg-ivory/10 hover:text-ivory" aria-label="Tutup menu" @click="menuOpen = false">
              <X class="h-5 w-5" :stroke-width="1.5" />
            </button>
          </div>
          <!-- Nav links -->
          <ul class="flex-1 overflow-y-auto px-5 py-4">
            <li v-for="link in links" :key="link.to">
              <Link
                :href="link.to"
                class="flex min-h-14 items-center rounded-lg border-l-2 px-4 font-display text-[1.2rem] transition-colors"
                :class="isActive(link.to) ? 'border-gold bg-ivory/[0.07] text-ivory' : 'border-transparent text-ivory/68 hover:bg-ivory/[0.05] hover:text-ivory'"
                :aria-current="isActive(link.to) ? 'page' : undefined"
              >
                {{ link.label }}
              </Link>
            </li>
          </ul>
          <!-- Drawer footer -->
          <div class="space-y-1 border-t border-ivory/10 px-5 py-5 pb-safe">
            <Link href="/akun" class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-[0.88rem] text-ivory/72 transition hover:bg-ivory/[0.06] hover:text-ivory">
              <User class="h-4 w-4 text-gold" :stroke-width="1.5" /> Akun saya
            </Link>
            <button
              class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-[0.88rem] text-ivory/72 transition hover:bg-ivory/[0.06] hover:text-ivory"
              @click="menuOpen = false; openDrawer()"
            >
              <ShoppingBag class="h-4 w-4 text-gold" :stroke-width="1.5" /> Keranjang
              <span v-if="count" class="ml-auto grid h-5 min-w-5 place-items-center rounded-full bg-gold px-1.5 text-[0.62rem] font-bold text-forest-deep">{{ count }}</span>
            </button>
            <Link href="/lacak-pesanan" class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-[0.88rem] text-ivory/72 transition hover:bg-ivory/[0.06] hover:text-ivory">
              <Search class="h-4 w-4 text-gold" :stroke-width="1.5" /> Lacak pesanan
            </Link>
            <a
              :href="whatsappHref()" target="_blank" rel="noopener"
              class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-[0.88rem] text-ivory/72 transition hover:bg-ivory/[0.06] hover:text-ivory"
            >
              <MessageCircle class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
              Chat WhatsApp
            </a>
          </div>
        </div>
      </Transition>
      <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
        <div v-if="menuOpen" class="fixed inset-0 z-[115] bg-forest-deep/65 backdrop-blur-sm lg:hidden" @click="menuOpen = false" />
      </Transition>
    </Teleport>

    <Teleport to="body">
      <!-- Search overlay -->
      <Transition
        enter-active-class="transition duration-[250ms] ease-calm" enter-from-class="-translate-y-4 opacity-0"
        leave-active-class="transition duration-200" leave-to-class="-translate-y-2 opacity-0"
      >
        <div
          v-if="searchOpen"
          class="fixed inset-x-0 top-0 z-[122] border-b border-line bg-surface shadow-[0_28px_70px_-38px_rgb(12_84_75/0.55)]"
          role="dialog"
          aria-modal="true"
          aria-label="Pencarian produk"
          @keydown.esc="searchOpen = false"
        >
          <div class="shell py-5 sm:py-7">
            <div class="flex items-center gap-3 rounded-xl border border-line bg-ivory/60 px-4 py-2 focus-within:border-forest-soft">
              <Search class="h-5 w-5 flex-none text-forest" :stroke-width="1.5" />
              <input
                v-model="query" autofocus type="search"
                placeholder="Cari kurma, sajadah, atau gift set"
                class="w-full bg-transparent py-2 font-display text-lg text-forest placeholder:text-muted/55 focus:outline-none sm:text-2xl"
              />
              <button class="grid h-10 w-10 flex-none place-items-center rounded-lg text-forest transition hover:bg-forest/10" aria-label="Tutup pencarian" @click="searchOpen = false">
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
            <p v-else class="mt-5 text-[0.78rem] text-muted">
              Pencarian populer: kurma ajwa, gift set, sajadah travel
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
