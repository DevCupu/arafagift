<script setup>
import { Link } from '@inertiajs/vue3'
import { Gift, Trash2 } from 'lucide-vue-next'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import QuantityInput from '@/components/ui/QuantityInput.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { formatIDR } from '@/composables/useFormat'
import { useCart } from '@/composables/useCart'

const cart = useCart()
</script>

<template>
  <div class="shell py-14 sm:py-20">
    <h1 class="text-[2.4rem] leading-none sm:text-[3rem]">Keranjang</h1>

    <div v-if="cart.items.value.length" class="mt-12 grid gap-12 lg:grid-cols-[1.6fr_1fr] lg:gap-16">
      <section>
        <ul class="divide-y divide-line border-y border-line">
          <li v-for="item in cart.items.value" :key="item.id" class="flex gap-5 py-6">
            <Link :href="`/produk/${item.slug}`" class="arch h-32 w-24 flex-none overflow-hidden border border-line sm:h-36 sm:w-28">
              <ProductArt :art="item.art" :tone="item.id" />
            </Link>
            <div class="flex flex-1 flex-col">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <p class="text-[0.65rem] uppercase tracking-[0.14em] text-olive">{{ item.category }}</p>
                  <h2 class="mt-1.5 font-display text-[1.35rem] leading-tight">
                    <Link :href="`/produk/${item.slug}`" class="link-underline">{{ item.name }}</Link>
                  </h2>
                  <p class="mt-1.5 text-[0.8rem] text-muted">{{ formatIDR(item.price) }} / pcs</p>
                </div>
                <button class="text-muted transition hover:text-danger" :aria-label="`Hapus ${item.name}`" @click="cart.remove(item.id)">
                  <Trash2 class="h-4 w-4" :stroke-width="1.4" />
                </button>
              </div>
              <div class="mt-auto flex items-end justify-between pt-4">
                <QuantityInput :model-value="item.qty" :max="Math.max(item.stock, 1)" @update:model-value="(v) => cart.setQty(item.id, v)" />
                <span class="font-display text-xl text-forest">{{ formatIDR(item.lineTotal) }}</span>
              </div>
            </div>
          </li>
        </ul>

        <div class="mt-6 flex items-start gap-3 border border-dashed border-gold/40 bg-gold/[0.07] px-5 py-4">
          <Gift class="mt-0.5 h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
          <p class="text-[0.82rem] leading-relaxed text-forest">
            <span class="font-semibold">Gratis kartu ucapan tulis tangan.</span>
            Tulis pesannya di halaman checkout, kami selipkan sebelum box ditutup.
          </p>
        </div>

        <Link href="/koleksi" class="link-underline mt-8 inline-block text-[0.85rem] text-forest">Lanjut belanja</Link>
      </section>

      <aside class="lg:sticky lg:top-28 lg:h-fit">
        <div class="border border-line bg-surface p-7">
          <h2 class="font-display text-2xl">Ringkasan</h2>
          <dl class="mt-6 space-y-3 text-[0.87rem]">
            <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="text-forest">{{ formatIDR(cart.subtotal.value) }}</dd></div>
            <div v-if="cart.savings.value" class="flex justify-between"><dt class="text-muted">Potongan</dt><dd class="text-olive">−{{ formatIDR(cart.savings.value) }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Ongkos kirim</dt><dd class="text-muted">Dihitung saat checkout</dd></div>
          </dl>
          <div class="mt-6 flex items-baseline justify-between border-t border-line pt-5">
            <span class="text-[0.85rem] text-muted">Total sementara</span>
            <span class="font-display text-3xl text-forest">{{ formatIDR(cart.subtotal.value) }}</span>
          </div>
          <AppButton to="/checkout" block size="lg" class="mt-6">Checkout</AppButton>
          <p class="mt-4 text-center text-[0.72rem] text-muted">Pembayaran aman · Transfer, QRIS, kartu kredit</p>
        </div>
      </aside>
    </div>

    <EmptyState v-else class="mt-12" title="Keranjang masih kosong" body="Lihat koleksi yang paling sering dipilih untuk keluarga dan sahabat.">
      <AppButton to="/koleksi">Jelajahi koleksi</AppButton>
    </EmptyState>
  </div>
</template>
