<script setup>
import { computed } from 'vue'
import AccountShell from '@/components/storefront/AccountShell.vue'
import AppButton from '@/components/ui/AppButton.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { orders, orderTotal } from '@/data/admin'
import { formatDate, formatIDR } from '@/composables/useFormat'

const props = defineProps({ id: { type: String, required: true } })
const order = computed(() => orders.find((o) => o.id === props.id))

const timeline = [
  ['Pesanan dibuat', true], ['Pembayaran diterima', true],
  ['Dikemas', true], ['Dikirim', false], ['Diterima', false],
]
</script>

<template>
  <AccountShell v-if="order" :title="order.id" :sub="`Dibuat ${formatDate(order.date)}`">
    <div class="grid gap-10 lg:grid-cols-[1.4fr_1fr]">
      <div>
        <div class="flex items-center gap-4"><StatusPill :status="order.status" /><span class="text-[0.8rem] text-muted">{{ order.shipping.courier }}</span></div>

        <!-- Urutan status pesanan memang berurutan -->
        <ol class="mt-8 border-l border-line pl-6">
          <li v-for="([label, done], i) in timeline" :key="label" class="relative pb-6 last:pb-0">
            <span
              class="absolute -left-[1.72rem] top-1 grid h-3 w-3 place-items-center rounded-full border"
              :class="done ? 'border-forest bg-forest' : 'border-line bg-surface'"
            />
            <p class="text-[0.88rem]" :class="done ? 'text-forest' : 'text-muted'">{{ label }}</p>
            <p v-if="done" class="mt-0.5 text-[0.72rem] text-muted">{{ formatDate(order.date) }}</p>
          </li>
        </ol>

        <ul class="mt-10 divide-y divide-line border-y border-line">
          <li v-for="(it, i) in order.items" :key="i" class="flex items-center gap-4 py-5">
            <span class="arch h-20 w-16 flex-none overflow-hidden border border-line"><ProductArt :art="it.art" :tone="i" /></span>
            <div class="flex-1">
              <p class="font-display text-[1.15rem] text-forest">{{ it.name }}</p>
              <p class="mt-1 text-[0.75rem] text-muted">{{ it.sku }} · {{ it.qty }} pcs</p>
            </div>
            <p class="text-[0.88rem] text-forest">{{ formatIDR(it.price * it.qty) }}</p>
          </li>
        </ul>
      </div>

      <aside class="space-y-4">
        <div class="border border-line bg-surface p-6">
          <h2 class="font-display text-xl">Ringkasan</h2>
          <dl class="mt-5 space-y-2.5 text-[0.85rem]">
            <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="text-forest">{{ formatIDR(orderTotal(order) - order.shipping.cost) }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Ongkir</dt><dd class="text-forest">{{ formatIDR(order.shipping.cost) }}</dd></div>
          </dl>
          <div class="mt-4 flex justify-between border-t border-line pt-4">
            <span class="text-[0.85rem] text-muted">Total</span>
            <span class="font-display text-2xl text-forest">{{ formatIDR(orderTotal(order)) }}</span>
          </div>
        </div>
        <div class="border border-line bg-surface p-6 text-[0.85rem] leading-relaxed text-muted">
          <h2 class="font-display text-xl text-forest">Alamat pengiriman</h2>
          <p class="mt-3">{{ order.customer }}</p>
          <p>{{ order.address }}</p>
          <p class="mt-2">{{ order.phone }}</p>
        </div>
        <AppButton href="https://wa.me/6281234567890" variant="quiet" block>Tanya tentang pesanan ini</AppButton>
      </aside>
    </div>
  </AccountShell>

  <AccountShell v-else title="Pesanan tidak ditemukan">
    <EmptyState title="Nomor pesanan tidak dikenali" body="Periksa kembali tautannya, atau lihat daftar pesanan Anda.">
      <AppButton to="/akun/pesanan">Ke daftar pesanan</AppButton>
    </EmptyState>
  </AccountShell>
</template>
