<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft, Printer } from 'lucide-vue-next'
import StatusPill from '@/components/admin/StatusPill.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { orders, orderStatuses, orderTotal } from '@/data/admin'
import { formatDateTime, formatIDR } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const { push } = useToast()
const order = computed(() => orders.find((o) => o.id === route.params.id))
const status = ref(order.value?.status ?? 'pending')

const save = () => push(`Status ${order.value.id} diperbarui`, { tone: 'success' })
</script>

<template>
  <div v-if="order">
    <router-link to="/admin/pesanan" class="inline-flex items-center gap-2 text-[0.8rem] text-muted transition hover:text-forest">
      <ArrowLeft class="h-3.5 w-3.5" /> Semua pesanan
    </router-link>

    <header class="mt-5 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">{{ order.id }}</h1>
        <p class="mt-3 text-[0.85rem] text-muted">{{ formatDateTime(order.date) }} · via {{ order.channel }}</p>
      </div>
      <div class="flex items-center gap-3">
        <StatusPill :status="order.status" />
        <AppButton variant="quiet" size="sm">
          <template #icon><Printer class="h-3.5 w-3.5" /></template>
          Cetak label
        </AppButton>
      </div>
    </header>

    <div class="mt-8 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
      <section class="border border-line bg-surface">
        <h2 class="border-b border-line px-6 py-5 font-display text-2xl">Item pesanan</h2>
        <ul class="divide-y divide-line">
          <li v-for="(it, i) in order.items" :key="i" class="flex items-center gap-4 px-6 py-4">
            <span class="arch h-16 w-12 flex-none overflow-hidden border border-line bg-ivory"><ProductArt :art="it.art" :tone="i" /></span>
            <span class="flex-1">
              <span class="block text-[0.9rem] text-forest">{{ it.name }}</span>
              <span class="text-[0.72rem] text-muted">{{ it.sku }} · {{ formatIDR(it.price) }} / pcs</span>
            </span>
            <span class="text-[0.85rem] text-muted">× {{ it.qty }}</span>
            <span class="w-32 text-right text-[0.9rem] text-forest">{{ formatIDR(it.price * it.qty) }}</span>
          </li>
        </ul>
        <dl class="space-y-2.5 border-t border-line px-6 py-5 text-[0.85rem]">
          <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd>{{ formatIDR(orderTotal(order) - order.shipping.cost) }}</dd></div>
          <div class="flex justify-between"><dt class="text-muted">Ongkir · {{ order.shipping.method }}</dt><dd>{{ formatIDR(order.shipping.cost) }}</dd></div>
          <div class="flex justify-between border-t border-line pt-3 text-[1rem]">
            <dt class="text-forest">Total</dt>
            <dd class="font-display text-2xl text-forest">{{ formatIDR(orderTotal(order)) }}</dd>
          </div>
        </dl>
      </section>

      <div class="space-y-6">
        <section class="border border-line bg-surface p-6">
          <h2 class="font-display text-2xl">Ubah status</h2>
          <label class="field-label mt-5" for="status">Status pesanan</label>
          <select id="status" v-model="status" class="field">
            <option v-for="s in orderStatuses" :key="s.id" :value="s.id">{{ s.label }}</option>
          </select>
          <label class="field-label mt-4" for="awb">Nomor resi</label>
          <input id="awb" :value="order.shipping.awb" class="field" placeholder="Belum ada resi" />
          <AppButton block class="mt-5" @click="save">Simpan perubahan</AppButton>
        </section>

        <section class="border border-line bg-surface p-6 text-[0.85rem] leading-relaxed text-muted">
          <h2 class="font-display text-2xl text-forest">Pelanggan</h2>
          <p class="mt-4 text-forest">{{ order.customer }}</p>
          <p>{{ order.email }}</p>
          <p>{{ order.phone }}</p>
          <p class="mt-4 border-t border-line pt-4">{{ order.address }}</p>
        </section>

        <section v-if="order.note" class="border border-dashed border-gold/40 bg-gold/[0.07] p-6">
          <h2 class="font-display text-xl text-forest">Catatan pembeli</h2>
          <p class="mt-3 font-display text-[1.15rem] italic leading-snug text-forest">"{{ order.note }}"</p>
        </section>
      </div>
    </div>
  </div>

  <EmptyState v-else title="Pesanan tidak ditemukan" body="Nomor pesanan tidak ada di sistem.">
    <AppButton to="/admin/pesanan">Ke daftar pesanan</AppButton>
  </EmptyState>
</template>
