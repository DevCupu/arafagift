<script setup>
import { computed, reactive, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { CircleCheck, CircleX, Clock, Copy, MessageCircle, Package, PackageCheck, PackageSearch, Search, Truck, Wallet } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import { statusMeta, orderTotal } from '@/data/admin'
import { formatDateTime, formatIDR } from '@/composables/useFormat'
import { useStore } from '@/composables/useStore'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  order: { type: Object, default: null },
  searched: { type: Boolean, default: false },
})

const errors = computed(() => usePage().props.errors)
const form = reactive({ order_number: '', phone: '' })
const loading = ref(false)
const { whatsappHref } = useStore()
const { push } = useToast()

const submit = () => {
  loading.value = true
  router.get('/lacak-pesanan', { order_number: form.order_number, phone: form.phone }, {
    preserveState: true,
    onFinish: () => { loading.value = false },
  })
}

const copyOrderNumber = () => {
  navigator.clipboard?.writeText(props.order.id)
  push('Nomor pesanan disalin', { tone: 'success' })
}

// ── Timeline pesanan ──
const steps = [
  { id: 'pending', label: 'Menunggu bayar', icon: Clock },
  { id: 'paid', label: 'Dibayar', icon: Wallet },
  { id: 'processing', label: 'Diproses', icon: PackageSearch },
  { id: 'shipped', label: 'Dikirim', icon: Truck },
  { id: 'completed', label: 'Selesai diterima', icon: PackageCheck },
]

const statusHelp = {
  pending: 'Menunggu konfirmasi pembayaran — admin kami akan menghubungi Anda via WhatsApp untuk instruksi pembayaran.',
  paid: 'Pembayaran sudah kami terima. Pesanan akan segera masuk proses pengemasan.',
  processing: 'Pesanan sedang dikemas oleh tim kami.',
  shipped: 'Paket sedang dalam perjalanan menuju alamat Anda.',
  completed: 'Pesanan sudah diterima. Terima kasih sudah berbelanja di ArafahGift!',
}

const isCancelled = computed(() => props.order?.status === 'cancelled')
const currentStepIndex = computed(() => steps.findIndex((s) => s.id === props.order?.status))
</script>

<template>
  <Head title="Lacak Pesanan">
    <meta name="robots" content="noindex,follow" />
  </Head>
  <div class="shell py-16 sm:py-24">
    <div class="mx-auto max-w-xl">
      <div v-reveal class="flex flex-col items-center text-center">
        <span class="grid h-14 w-14 place-items-center rounded-full border border-gold/40 bg-gold/10 text-forest">
          <Package class="h-6 w-6" :stroke-width="1.5" />
        </span>
        <h1 class="mt-5 text-[2.1rem] leading-none">Lacak pesanan</h1>
        <p class="mt-3 max-w-sm text-[0.85rem] text-muted">Masukkan nomor pesanan dan nomor WhatsApp yang dipakai saat checkout untuk memantau statusnya secara langsung.</p>
      </div>

      <form v-reveal="80" class="mt-8 space-y-5 border border-line bg-surface p-6 sm:p-7" @submit.prevent="submit">
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
        <AppButton type="submit" size="lg" block :loading="loading">
          <template #icon><Search class="h-4 w-4" /></template>
          Cari pesanan
        </AppButton>
      </form>

      <Transition
        enter-active-class="transition duration-500 ease-calm" enter-from-class="opacity-0 translate-y-3"
        leave-active-class="transition duration-200" leave-to-class="opacity-0"
        mode="out-in"
      >
        <div v-if="order" :key="order.id" class="mt-10">
          <header class="flex items-end justify-between gap-4">
            <div>
              <button type="button" class="group inline-flex items-center gap-2 text-[1.6rem] leading-none text-forest" @click="copyOrderNumber">
                {{ order.id }}
                <Copy class="h-4 w-4 text-muted opacity-40 transition group-hover:opacity-100" :stroke-width="1.5" />
              </button>
              <p class="mt-2 text-[0.8rem] text-muted">{{ formatDateTime(order.date) }}</p>
            </div>
            <span class="text-[0.8rem] text-forest">{{ statusMeta(order.status).label }}</span>
          </header>

          <!-- ════════ TIMELINE STATUS ════════ -->
          <p class="mt-8 text-[0.72rem] uppercase tracking-[0.12em] text-muted">Status pesanan</p>
          <div v-if="isCancelled" class="mt-3 flex items-start gap-3 border border-danger/30 bg-danger/5 p-5">
            <CircleX class="h-5 w-5 flex-none text-danger" :stroke-width="1.5" />
            <div>
              <p class="text-[0.9rem] font-medium text-danger">Pesanan dibatalkan</p>
              <p class="mt-1 text-[0.82rem] text-muted">Pesanan ini tidak akan diproses lebih lanjut.</p>
            </div>
          </div>

          <ol v-else class="mt-4">
            <li v-for="(step, i) in steps" :key="step.id" class="relative flex gap-4 pb-7 last:pb-0">
              <span
                v-if="i < steps.length - 1"
                class="absolute left-[15px] top-8 h-full w-px transition-colors duration-700"
                :class="i < currentStepIndex ? 'bg-forest' : 'bg-line'"
              />
              <span
                class="relative z-10 grid h-8 w-8 flex-none place-items-center rounded-full border transition-all duration-500"
                :class="i <= currentStepIndex ? 'border-forest bg-forest text-ivory' : 'border-line bg-ivory text-muted'"
              >
                <span v-if="i === currentStepIndex" class="absolute inset-0 rounded-full bg-forest/50 animate-ping" />
                <component :is="i < currentStepIndex ? CircleCheck : step.icon" class="relative h-4 w-4" :stroke-width="1.6" />
              </span>
              <div class="pt-1">
                <p class="text-[0.9rem] font-medium transition-colors duration-500" :class="i <= currentStepIndex ? 'text-forest' : 'text-muted'">{{ step.label }}</p>
                <p v-if="i === currentStepIndex" class="mt-0.5 max-w-xs text-[0.78rem] leading-relaxed text-olive">{{ statusHelp[step.id] }}</p>
              </div>
            </li>
          </ol>

          <!-- ════════ INFO PENGIRIMAN ════════ -->
          <p class="mt-8 text-[0.72rem] uppercase tracking-[0.12em] text-muted">Info pengiriman</p>
          <div class="mt-3 grid gap-5 border border-line bg-surface p-5 text-[0.85rem] sm:grid-cols-2">
            <div>
              <p class="text-[0.7rem] uppercase tracking-[0.12em] text-muted">Penerima</p>
              <p class="mt-1.5 text-forest">{{ order.customer }}</p>
            </div>
            <div>
              <p class="text-[0.7rem] uppercase tracking-[0.12em] text-muted">Metode pembayaran</p>
              <p class="mt-1.5 text-forest">{{ order.payment }}</p>
            </div>
            <div v-if="order.address" class="sm:col-span-2">
              <p class="text-[0.7rem] uppercase tracking-[0.12em] text-muted">Alamat pengiriman</p>
              <p class="mt-1.5 text-forest">{{ order.address }}</p>
            </div>
            <div v-if="order.note" class="sm:col-span-2">
              <p class="text-[0.7rem] uppercase tracking-[0.12em] text-muted">Catatan</p>
              <p class="mt-1.5 text-forest">{{ order.note }}</p>
            </div>
          </div>

          <!-- ════════ RINCIAN PESANAN ════════ -->
          <p class="mt-8 text-[0.72rem] uppercase tracking-[0.12em] text-muted">Rincian pesanan</p>
          <div class="mt-3 divide-y divide-line border-y border-line text-[0.87rem]">
            <div v-for="(it, i) in order.items" :key="i" class="flex items-center gap-3 py-3.5">
              <span class="arch h-11 w-9 flex-none overflow-hidden border border-line bg-ivory"><ProductArt :art="it.art" :tone="i" /></span>
              <dt class="flex-1 text-muted">{{ it.name }} × {{ it.qty }}</dt>
              <dd class="text-forest">{{ formatIDR(it.price * it.qty) }}</dd>
            </div>
            <div class="flex justify-between gap-6 py-3.5">
              <dt class="text-muted">Ongkir{{ order.shipping.courier && order.shipping.courier !== 'Belum ditentukan' ? ` — ${order.shipping.courier}` : '' }}</dt>
              <dd class="text-forest">{{ order.shipping.cost ? formatIDR(order.shipping.cost) : 'Belum ditentukan' }}</dd>
            </div>
            <div v-if="order.shipping.awb" class="flex justify-between gap-6 py-3.5">
              <dt class="text-muted">No. resi</dt>
              <dd class="text-forest">{{ order.shipping.awb }}</dd>
            </div>
          </div>
          <div class="mt-4 flex items-baseline justify-between">
            <span class="text-[0.85rem] text-muted">Total</span>
            <span class="font-display text-2xl text-forest">{{ formatIDR(orderTotal(order)) }}</span>
          </div>

          <p class="mt-8 text-center text-[0.8rem] text-muted">
            Ada pertanyaan soal pesanan ini?
            <a :href="whatsappHref(`Halo ArafahGift, saya mau tanya soal pesanan ${order.id}.`)" target="_blank" rel="noopener" class="link-underline inline-flex items-center gap-1 text-forest">
              <MessageCircle class="h-3.5 w-3.5" />Chat kami di WhatsApp
            </a>
          </p>
        </div>

        <EmptyState
          v-else-if="searched"
          key="empty"
          class="mt-10"
          title="Pesanan tidak ditemukan"
          body="Periksa kembali nomor pesanan dan nomor WhatsApp yang Anda masukkan."
        />
      </Transition>
    </div>
  </div>
</template>
