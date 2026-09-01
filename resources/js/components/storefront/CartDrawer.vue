<script setup>
import { onBeforeUnmount, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Gift, ShoppingBag, Trash2, X } from 'lucide-vue-next'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import QuantityInput from '@/components/ui/QuantityInput.vue'
import { formatIDR } from '@/composables/useFormat'
import { useCart } from '@/composables/useCart'

const cart = useCart()

const onKey = (e) => { if (e.key === 'Escape') cart.closeDrawer() }

watch(
  () => cart.drawerOpen.value,
  (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
    open ? window.addEventListener('keydown', onKey) : window.removeEventListener('keydown', onKey)
  },
)
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKey)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0" leave-active-class="transition duration-[250ms]" leave-to-class="opacity-0">
      <div v-if="cart.drawerOpen.value" class="fixed inset-0 z-[124] bg-forest-deep/40 backdrop-blur-[2px]" @click="cart.closeDrawer()" />
    </Transition>

    <Transition
      enter-active-class="transition duration-[420ms] ease-calm" enter-from-class="translate-x-full"
      leave-active-class="transition duration-300 ease-calm" leave-to-class="translate-x-full"
    >
      <aside
        v-if="cart.drawerOpen.value"
        class="fixed inset-y-0 right-0 z-[125] flex w-full flex-col border-l border-forest-soft/20 bg-ivory sm:max-w-md"
        role="dialog" aria-modal="true" aria-label="Keranjang belanja"
      >
        <header class="flex items-center justify-between border-b border-line bg-forest-deep px-6 py-5">
          <h2 class="font-display text-2xl text-ivory">
            Keranjang
            <span class="ml-1 align-middle text-[0.8rem] text-ivory/50">({{ cart.count.value }})</span>
          </h2>
          <button class="grid h-9 w-9 place-items-center text-ivory/70 transition hover:text-ivory" aria-label="Tutup keranjang" @click="cart.closeDrawer()">
            <X class="h-[18px] w-[18px]" :stroke-width="1.5" />
          </button>
        </header>

        <div v-if="cart.items.value.length" class="flex-1 overflow-y-auto px-6">
          <ul class="divide-y divide-line">
            <li v-for="item in cart.items.value" :key="item.id" class="flex gap-4 py-5">
              <Link :href="`/produk/${item.slug}`" class="arch h-24 w-[72px] flex-none overflow-hidden border border-line" @click="cart.closeDrawer()">
                <img v-if="item.image" :src="item.image" :alt="item.name" loading="lazy" class="h-full w-full object-cover" />
                <ProductArt v-else :art="item.art" :tone="item.id" />
              </Link>
              <div class="flex flex-1 flex-col">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="text-[0.65rem] uppercase tracking-[0.14em] text-olive">{{ item.category }}</p>
                    <h3 class="mt-1 font-display text-[1.1rem] leading-tight">
                      <Link :href="`/produk/${item.slug}`" @click="cart.closeDrawer()">{{ item.name }}</Link>
                    </h3>
                  </div>
                  <button class="mt-0.5 text-muted transition hover:text-danger" :aria-label="`Hapus ${item.name}`" @click="cart.remove(item.id)">
                    <Trash2 class="h-4 w-4" :stroke-width="1.4" />
                  </button>
                </div>
                <div class="mt-auto flex items-end justify-between pt-3">
                  <QuantityInput
                    size="sm" :model-value="item.qty" :max="Math.max(item.stock, 1)"
                    @update:model-value="(v) => cart.setQty(item.id, v)"
                  />
                  <span class="text-[0.85rem] font-semibold text-forest">{{ formatIDR(item.lineTotal) }}</span>
                </div>
              </div>
            </li>
          </ul>

          <div class="mb-6 mt-2 flex items-start gap-3 border border-dashed border-gold/40 bg-gold/[0.07] px-4 py-3.5">
            <Gift class="mt-0.5 h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
            <p class="text-[0.78rem] leading-relaxed text-forest">
              <span class="font-semibold">Gratis kartu ucapan.</span>
              Tulis pesan Anda saat checkout — kami tulis tangan sebelum box ditutup.
            </p>
          </div>
        </div>

        <div v-else class="flex flex-1 flex-col items-center justify-center px-10 text-center">
          <div class="arch grid h-20 w-16 place-items-end border border-forest/20 bg-forest/[0.07] pb-3">
            <ShoppingBag class="h-5 w-5 text-gold" :stroke-width="1.3" />
          </div>
          <h3 class="mt-6 font-display text-2xl">Keranjang masih kosong</h3>
          <p class="mt-2 text-[0.85rem] leading-relaxed text-muted">
            Mulai dari gift set yang paling sering dipilih, atau lihat seluruh koleksi.
          </p>
          <AppButton to="/koleksi" class="mt-6" @click="cart.closeDrawer()">Jelajahi koleksi</AppButton>
        </div>

        <footer v-if="cart.items.value.length" class="border-t border-line bg-surface px-5 py-5 sm:px-6">
          <div class="flex items-baseline justify-between">
            <span class="text-[0.85rem] text-muted">Subtotal</span>
            <span class="font-display text-2xl text-forest">{{ formatIDR(cart.subtotal.value) }}</span>
          </div>
          <p v-if="cart.savings.value" class="mt-1 text-right text-[0.75rem] text-olive">
            Anda hemat {{ formatIDR(cart.savings.value) }}
          </p>
          <p class="mt-1 text-[0.75rem] text-muted">Ongkos kirim dihitung di halaman checkout.</p>
          <AppButton to="/checkout" block size="lg" class="mt-4" @click="cart.closeDrawer()">Checkout</AppButton>
          <button class="mt-3 w-full text-center text-[0.78rem] text-muted transition hover:text-forest" @click="cart.closeDrawer()">
            Lanjut belanja
          </button>
          <!-- Safe area padding for notched phones -->
          <div class="h-safe-bottom" />
        </footer>
      </aside>
    </Transition>
  </Teleport>
</template>
