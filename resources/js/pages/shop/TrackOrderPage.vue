<script setup>
import { computed, reactive, ref } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { CircleAlert, CircleCheck, CircleX, Clock, Copy, MessageCircle, Package, PackageCheck, PackageSearch, Search, ShieldCheck, Truck, Upload, Wallet } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import { paymentPillClass, paymentStatusMeta, orderTotal, orderStepLabel } from '@/data/admin'
import { formatDateTime, formatIDR } from '@/composables/useFormat'
import { useStore } from '@/composables/useStore'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  order: { type: Object, default: null },
  searched: { type: Boolean, default: false },
  seoHead: { type: Object, required: true },
})

const errors = computed(() => usePage().props.errors)
const form = reactive({ order_number: '', phone: '' })
const loading = ref(false)
const { store, whatsappHref } = useStore()
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

// ── Timeline pesanan (label menyesuaikan antar / ambil di toko) ──
const isPickup = computed(() => props.order?.fulfillment === 'pickup')
const steps = computed(() => [
  { id: 'pending', label: 'Menunggu bayar', icon: Clock },
  { id: 'paid', label: 'Dibayar', icon: Wallet },
  { id: 'processing', label: 'Diproses', icon: PackageSearch },
  isPickup.value
    ? { id: 'shipped', label: 'Siap diambil', icon: PackageCheck }
    : { id: 'shipped', label: 'Dikirim', icon: Truck },
  isPickup.value
    ? { id: 'completed', label: 'Sudah diambil', icon: PackageCheck }
    : { id: 'completed', label: 'Selesai diterima', icon: PackageCheck },
])

const statusHelp = computed(() => ({
  pending: isWaiting.value
    ? 'Bukti pembayaran sudah diterima. Admin akan memverifikasi maksimal 1×24 jam.'
    : 'Menunggu pembayaran. Unggah bukti transfer di bawah agar pesanan segera diproses.',
  paid: 'Pembayaran sudah kami terima. Pesanan akan segera masuk proses pengemasan.',
  processing: 'Pesanan sedang dikemas oleh tim kami.',
  shipped: isPickup.value
    ? 'Pesanan siap diambil di toko. Tunjukkan nomor pesanan saat datang.'
    : 'Paket sedang dalam perjalanan menuju alamat Anda.',
  completed: isPickup.value
    ? 'Pesanan sudah diambil. Terima kasih sudah berbelanja di ArafahGift!'
    : 'Pesanan sudah diterima. Terima kasih sudah berbelanja di ArafahGift!',
}))

const isCancelled = computed(() => props.order?.status === 'cancelled')
const paymentStatus = computed(() => props.order?.paymentStatus ?? 'unpaid')
const isUnpaid = computed(() => paymentStatus.value === 'unpaid')
const isWaiting = computed(() => paymentStatus.value === 'waiting_confirmation')
const isPaid = computed(() => paymentStatus.value === 'paid')
const isCod = computed(() => props.order?.paymentCode === 'cod')
const paymentProof = computed(() => props.order?.paymentProof ?? null)
const rejectedProof = computed(() => (paymentProof.value?.status === 'rejected' ? paymentProof.value : null))
const currentStepIndex = computed(() => steps.value.findIndex((s) => s.id === props.order?.status))

// ── Unggah bukti pembayaran (tamu tetap bisa, lewat token lacak) ──
const proofInput = ref(null)
const uploadForm = useForm({ proof: null, amount: '', sender_name: '', note: '' })

const onProofChange = (event) => {
  uploadForm.proof = event.target.files?.[0] ?? null
}

const submitProof = () => {
  uploadForm.post(`/lacak/${props.order.trackingToken}/bukti-bayar`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      uploadForm.reset()
      if (proofInput.value) proofInput.value.value = ''
      push('Bukti pembayaran terkirim. Menunggu verifikasi admin.', { tone: 'success' })
    },
    onError: () => push('Periksa kembali file bukti dan nominal transfer.', { tone: 'danger' }),
  })
}


const cancelOrder = () => {
  if (!window.confirm('Batalkan pesanan ini? Tindakan ini tidak bisa dibatalkan.')) return
  router.post(`/lacak/${props.order.trackingToken}/batal`, {}, {
    preserveScroll: true,
    onSuccess: () => push('Pesanan dibatalkan.', { tone: 'success' }),
  })
}

// Instruksi bayar manual, termasuk rekening toko untuk transfer.
const paymentInfo = computed(() => {
  const order = props.order
  if (!order) return ''

  if (order.paymentCode === 'transfer') {
    return [
      store.value.bankName && `Bank ${store.value.bankName}`,
      store.value.bankAccountNumber && `No. rekening ${store.value.bankAccountNumber}`,
      store.value.bankAccountName && `a.n. ${store.value.bankAccountName}`,
      order.paymentInstructions || store.value.paymentInstructions,
    ].filter(Boolean).join('\n')
  }

  return order.paymentInstructions || store.value.paymentInstructions || 'Instruksi pembayaran dikirim admin melalui WhatsApp.'
})
</script>

<template>
  <Head :title="seoHead.title" />
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
            <div class="flex flex-col items-end gap-1.5">
              <span class="text-[0.8rem] text-forest">{{ orderStepLabel(order.status, order.fulfillment) }}</span>
              <span
                class="rounded-full border px-2.5 py-0.5 text-[0.66rem] font-semibold uppercase tracking-[0.08em]"
                :class="paymentPillClass(order.paymentStatus)"
              >{{ paymentStatusMeta(order.paymentStatus).label }}</span>
            </div>
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

          <!-- ════════ STATUS PEMBAYARAN ════════ -->
          <div v-if="isPaid && !isCancelled" class="mt-8 border border-olive/40 bg-olive/5 p-5">
            <p class="flex items-center gap-2 text-[0.7rem] uppercase tracking-[0.12em] text-muted">
              <CircleCheck class="h-3.5 w-3.5 text-olive" :stroke-width="1.5" /> Pembayaran lunas
            </p>
            <p class="mt-2 text-[0.92rem] font-semibold text-forest">{{ order.payment }}</p>
            <p v-if="order.paymentConfirmedAt" class="mt-1 text-[0.82rem] text-muted">Dikonfirmasi {{ formatDateTime(order.paymentConfirmedAt) }}</p>
            <p v-if="order.paymentReference" class="mt-1 text-[0.82rem] text-muted">Referensi: {{ order.paymentReference }}</p>
          </div>

          <div v-else-if="isWaiting && !isCancelled" class="mt-8 border border-sky-300 bg-sky-50 p-5">
            <p class="flex items-center gap-2 text-[0.7rem] uppercase tracking-[0.12em] text-sky-800">
              <ShieldCheck class="h-3.5 w-3.5" :stroke-width="1.5" /> Menunggu verifikasi
            </p>
            <p class="mt-2 text-[0.92rem] font-semibold text-forest">Bukti pembayaran sudah kami terima</p>
            <p class="mt-1 text-[0.82rem] leading-relaxed text-forest/90">Admin akan memeriksa bukti Anda maksimal 1×24 jam. Buka kembali halaman ini untuk melihat status terbaru.</p>
            <dl v-if="paymentProof" class="mt-4 space-y-1.5 border-t border-sky-200 pt-3 text-[0.8rem]">
              <div class="flex justify-between gap-4"><dt class="text-muted">File</dt><dd class="truncate text-forest">{{ paymentProof.fileName }}</dd></div>
              <div v-if="paymentProof.amount" class="flex justify-between gap-4"><dt class="text-muted">Nominal</dt><dd class="text-forest">{{ formatIDR(paymentProof.amount) }}</dd></div>
              <div v-if="paymentProof.senderName" class="flex justify-between gap-4"><dt class="text-muted">Pengirim</dt><dd class="text-forest">{{ paymentProof.senderName }}</dd></div>
              <div class="flex justify-between gap-4"><dt class="text-muted">Dikirim</dt><dd class="text-forest">{{ formatDateTime(paymentProof.submittedAt) }}</dd></div>
            </dl>
            <p class="mt-3 text-[0.75rem] leading-relaxed text-muted">
              Salah unggah file?
              <a :href="whatsappHref(`Halo ArafahGift, saya perlu mengganti bukti pembayaran untuk pesanan ${order.id}.`)" target="_blank" rel="noopener" class="link-underline text-forest">Hubungi kami di WhatsApp</a>.
            </p>
          </div>

          <div v-else-if="isUnpaid && !isCancelled" class="mt-8 border border-gold/40 bg-gold/[0.07] p-5">
            <p class="flex items-center gap-2 text-[0.7rem] uppercase tracking-[0.12em] text-muted">
              <Wallet class="h-3.5 w-3.5 text-gold" :stroke-width="1.5" /> Instruksi pembayaran
            </p>
            <p class="mt-2 text-[0.92rem] font-semibold text-forest">{{ order.payment }}</p>
            <p v-if="order.paymentDueAt" class="mt-1 text-[0.78rem] font-semibold text-danger">Bayar sebelum {{ formatDateTime(order.paymentDueAt) }}</p>
            <p class="mt-1.5 whitespace-pre-line text-[0.82rem] leading-relaxed text-forest/90">{{ paymentInfo }}</p>
            <img
              v-if="order.paymentCode === 'qris' && store.qrisImageUrl"
              :src="store.qrisImageUrl" alt="Kode QRIS ArafahGift"
              class="mt-3 h-48 w-48 border border-line bg-surface object-contain p-2"
            />

            <div v-if="rejectedProof" class="mt-4 border border-danger/30 bg-danger/5 p-4">
              <p class="flex items-center gap-1.5 text-[0.8rem] font-semibold text-danger">
                <CircleAlert class="h-3.5 w-3.5" :stroke-width="1.5" /> Bukti sebelumnya ditolak
              </p>
              <p class="mt-1 text-[0.78rem] leading-relaxed text-forest/90">{{ rejectedProof.rejectionReason || 'Silakan unggah ulang bukti yang lebih jelas.' }}</p>
            </div>

            <form v-if="!isCod" class="mt-5 border-t border-gold/30 pt-4" @submit.prevent="submitProof">
              <p class="text-[0.8rem] font-semibold text-forest">Unggah bukti pembayaran</p>
              <p class="mt-1 text-[0.72rem] leading-relaxed text-muted">JPG, PNG, WebP, atau PDF maksimal 5 MB. Setelah dikirim, admin memverifikasi maksimal 1×24 jam.</p>
              <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                  <label class="field-label" :for="`proof-${order.id}`">File bukti</label>
                  <input :id="`proof-${order.id}`" ref="proofInput" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="field text-xs" @change="onProofChange" />
                  <p v-if="uploadForm.errors.proof" class="mt-1 text-[0.72rem] text-danger">{{ uploadForm.errors.proof }}</p>
                </div>
                <div>
                  <label class="field-label" :for="`amount-${order.id}`">Nominal transfer</label>
                  <input :id="`amount-${order.id}`" v-model="uploadForm.amount" type="number" min="0" class="field" placeholder="150000" />
                  <p v-if="uploadForm.errors.amount" class="mt-1 text-[0.72rem] text-danger">{{ uploadForm.errors.amount }}</p>
                </div>
                <div>
                  <label class="field-label" :for="`sender-${order.id}`">Nama pengirim</label>
                  <input :id="`sender-${order.id}`" v-model="uploadForm.sender_name" maxlength="100" class="field" placeholder="Nama di rekening" />
                </div>
                <div class="sm:col-span-2">
                  <label class="field-label" :for="`proof-note-${order.id}`">Catatan (opsional)</label>
                  <input :id="`proof-note-${order.id}`" v-model="uploadForm.note" maxlength="500" class="field" placeholder="Contoh: transfer dari rekening BSI" />
                </div>
              </div>
              <AppButton type="submit" class="mt-4" block :loading="uploadForm.processing">
                <template #icon><Upload class="h-4 w-4" /></template>
                Kirim bukti pembayaran
              </AppButton>
              <p class="mt-2 text-[0.72rem] leading-relaxed text-muted">
                Lebih nyaman lewat WhatsApp?
                <a :href="whatsappHref(`Halo ArafahGift, saya mau kirim bukti pembayaran untuk pesanan ${order.id}.`)" target="_blank" rel="noopener" class="link-underline text-forest">Kirim bukti ke WhatsApp kami</a>.
              </p>
            </form>
            <p v-else class="mt-4 border-t border-gold/30 pt-3 text-[0.78rem] leading-relaxed text-muted">
              Pesanan COD dibayar saat barang diterima, jadi tidak perlu unggah bukti sekarang.
            </p>
          </div>

          <!-- ════════ INFO PENGIRIMAN ════════ -->
          <p class="mt-8 text-[0.72rem] uppercase tracking-[0.12em] text-muted">Info pengiriman</p>
          <div class="mt-3 grid gap-5 border border-line bg-surface p-5 text-[0.85rem] sm:grid-cols-2">
            <div>
              <p class="text-[0.7rem] uppercase tracking-[0.12em] text-muted">Penerima</p>
              <p class="mt-1.5 text-forest">{{ order.customer }}</p>
            </div>
            <div>
              <p class="text-[0.7rem] uppercase tracking-[0.12em] text-muted">Metode pembayaran</p>
              <p class="mt-1.5 text-forest">{{ order.payment }} <span class="text-muted">· {{ paymentStatusMeta(order.paymentStatus).label }}</span></p>
            </div>
            <div v-if="order.address" class="sm:col-span-2">
              <p class="text-[0.7rem] uppercase tracking-[0.12em] text-muted">Alamat pengiriman</p>
              <p class="mt-1.5 text-forest">{{ order.address }}</p>
            </div>
            <div v-else-if="isPickup" class="sm:col-span-2">
              <p class="text-[0.7rem] uppercase tracking-[0.12em] text-muted">Ambil di toko</p>
              <p class="mt-1.5 text-forest">{{ store.address || 'Alamat toko dikonfirmasi via WhatsApp.' }}</p>
              <p v-if="store.businessHours" class="mt-0.5 text-[0.8rem] text-muted">Jam buka: {{ store.businessHours }}</p>
              <p v-if="store.pickupInstructions" class="mt-1.5 whitespace-pre-line text-[0.8rem] leading-relaxed text-muted">{{ store.pickupInstructions }}</p>
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
            <div v-if="order.discount > 0" class="flex justify-between gap-6 py-3.5">
              <dt class="text-muted">Diskon<span v-if="order.voucherCode"> ({{ order.voucherCode }})</span></dt>
              <dd class="font-semibold text-olive">-{{ formatIDR(order.discount) }}</dd>
            </div>
            <div class="flex justify-between gap-6 py-3.5">
              <dt class="text-muted">{{ isPickup ? 'Pengambilan' : 'Ongkir' }}{{ !isPickup && order.shipping.courier && order.shipping.courier !== 'Belum ditentukan' ? ` — ${order.shipping.courier}` : '' }}</dt>
              <dd class="text-forest">
                <template v-if="isPickup">Gratis · ambil di toko</template>
                <template v-else>{{ order.shipping.cost ? formatIDR(order.shipping.cost) : 'Belum ditentukan' }}</template>
              </dd>
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

          <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <a :href="`/lacak/${order.trackingToken}/invoice`" target="_blank" rel="noopener" class="link-underline text-[0.8rem] text-forest">Lihat / cetak invoice</a>
            <button v-if="order.canCancel" type="button" class="text-[0.8rem] text-danger underline underline-offset-4" @click="cancelOrder">Batalkan pesanan</button>
          </div>
          <p v-if="errors.cancel" class="mt-2 text-center text-[0.72rem] text-danger">{{ errors.cancel }}</p>

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
