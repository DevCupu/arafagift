<script setup>
import { computed } from 'vue'
import { reactive } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { statusMeta, orderTotal } from '@/data/admin'
import { formatDateTime, formatIDR } from '@/composables/useFormat'

const props = defineProps({
  order: { type: Object, default: null },
  searched: { type: Boolean, default: false },
})

const errors = computed(() => usePage().props.errors)
const form = reactive({ order_number: '', phone: '' })

const submit = () => {
  router.get('/lacak-pesanan', { order_number: form.order_number, phone: form.phone }, { preserveState: true })
}
</script>

<template>
  <Head title="Lacak Pesanan">
    <meta name="robots" content="noindex,follow" />
  </Head>
  <div class="shell py-16 sm:py-24">
    <div class="mx-auto max-w-xl">
      <h1 class="text-[2.1rem] leading-none">Lacak pesanan</h1>
      <p class="mt-3 text-[0.85rem] text-muted">Masukkan nomor pesanan dan nomor WhatsApp yang dipakai saat checkout.</p>

      <div class="mt-8 space-y-5">
        <div>
          <label class="field-label" for="order_number">Nomor pesanan</label>
          <input id="order_number" v-model="form.order_number" class="field" placeholder="AGF-12345" />
          <p v-if="errors.order_number" class="mt-1.5 text-[0.72rem] text-danger">{{ errors.order_number }}</p>
        </div>
        <div>
          <label class="field-label" for="phone">Nomor WhatsApp</label>
          <input id="phone" v-model="form.phone" class="field" placeholder="08xx xxxx xxxx" />
          <p v-if="errors.phone" class="mt-1.5 text-[0.72rem] text-danger">{{ errors.phone }}</p>
        </div>
        <AppButton size="lg" @click="submit">
          <template #icon><Search class="h-4 w-4" /></template>
          Cari pesanan
        </AppButton>
      </div>

      <div v-if="order" class="mt-10">
        <header class="flex items-end justify-between gap-4">
          <div>
            <h2 class="text-[1.6rem] leading-none">{{ order.id }}</h2>
            <p class="mt-2 text-[0.8rem] text-muted">{{ formatDateTime(order.date) }}</p>
          </div>
          <span class="text-[0.8rem] text-forest">{{ statusMeta(order.status).label }}</span>
        </header>

        <dl class="mt-6 divide-y divide-line border-y border-line text-[0.87rem]">
          <div v-for="(it, i) in order.items" :key="i" class="flex justify-between gap-6 py-3">
            <dt class="text-muted">{{ it.name }} × {{ it.qty }}</dt>
            <dd class="text-forest">{{ formatIDR(it.price * it.qty) }}</dd>
          </div>
          <div class="flex justify-between gap-6 py-3">
            <dt class="text-muted">Ongkir</dt>
            <dd class="text-forest">{{ order.shipping.cost ? formatIDR(order.shipping.cost) : 'Belum ditentukan' }}</dd>
          </div>
        </dl>
        <div class="mt-4 flex items-baseline justify-between">
          <span class="text-[0.85rem] text-muted">Total</span>
          <span class="font-display text-2xl text-forest">{{ formatIDR(orderTotal(order)) }}</span>
        </div>
      </div>

      <EmptyState
        v-else-if="searched"
        class="mt-10"
        title="Pesanan tidak ditemukan"
        body="Periksa kembali nomor pesanan dan nomor WhatsApp yang Anda masukkan."
      />
    </div>
  </div>
</template>
