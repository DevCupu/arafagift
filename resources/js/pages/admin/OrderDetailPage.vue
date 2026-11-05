<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { computed, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Check, ExternalLink, FileText, MessageCircle, Printer, ShieldCheck, Trash2, X } from 'lucide-vue-next'
import StatusPill from '@/components/admin/StatusPill.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { orderStatuses, orderTotal, orderValueLabel, paymentPillClass, paymentStatusMeta, manualPaymentStatuses, fulfillmentMeta } from '@/data/admin'
import { formatDateTime, formatIDR } from '@/composables/useFormat'
import { buildWhatsAppHref } from '@/composables/whatsapp'
import { useToast } from '@/composables/useToast'

const props = defineProps({ order: { type: Object, default: null } })
const { push } = useToast()
const order = props.order
const isPickup = order?.fulfillment === 'pickup'

const paymentReference = ref(order?.paymentReference ?? '')
const paymentProofs = computed(() => order?.paymentProofs ?? [])
const isWaitingVerification = computed(() => order?.paymentStatus === 'waiting_confirmation')
const isOverdue = computed(() =>
  !!order?.paymentDueAt
  && order.paymentStatus === 'unpaid'
  && new Date(order.paymentDueAt).getTime() < Date.now(),
)

const form = useForm({
  status: order?.status ?? 'pending',
  awb: order?.shipping.awb ?? '',
  shippingCost: order?.shipping.cost ?? 0,
  adminNote: order?.admin_note ?? '',
})

const markPayment = (status) => {
  router.patch(`/admin/pesanan/${order.id}/pembayaran`, {
    payment_status: status,
    payment_reference: paymentReference.value || null,
  }, {
    preserveScroll: true,
    onSuccess: () => push(`Pembayaran ${order.id}: ${paymentStatusMeta(status).label}`, { tone: 'success' }),
  })
}

const verifyProof = (proof) => {
  router.post(`/admin/pesanan/${order.id}/pembayaran/${proof.id}/verifikasi`, {
    payment_reference: paymentReference.value || null,
  }, {
    preserveScroll: true,
    onSuccess: () => push(`Pembayaran ${order.id} diverifikasi`, { tone: 'success' }),
  })
}

const rejectingProofId = ref(null)
const rejectionReason = ref('')
const rejectionProcessing = ref(false)

const startReject = (proof) => {
  rejectingProofId.value = proof.id
  rejectionReason.value = ''
}

const cancelReject = () => {
  rejectingProofId.value = null
  rejectionReason.value = ''
}

const rejectProof = (proof) => {
  const reason = rejectionReason.value.trim()
  if (!reason) return

  router.post(`/admin/pesanan/${order.id}/pembayaran/${proof.id}/tolak`, {
    rejection_reason: reason,
  }, {
    preserveScroll: true,
    onStart: () => { rejectionProcessing.value = true },
    onFinish: () => { rejectionProcessing.value = false },
    onSuccess: () => {
      cancelReject()
      push(`Bukti pembayaran ${order.id} ditolak`, { tone: 'success' })
    },
  })
}

const customerWaHref = computed(() =>
  buildWhatsAppHref(
    order?.phone,
    `Halo ${order?.customer ?? 'kak'}, kami dari ArafahGift ingin mengonfirmasi pembayaran pesanan ${order?.id ?? ''}.`,
  ),
)

const save = () => {
  form.put(`/admin/pesanan/${order.id}`, {
    preserveScroll: true,
    onSuccess: () => push(`Status ${order.id} diperbarui`, { tone: 'success' }),
  })
}

const destroyOrder = () => {
  if (!confirm(`Hapus pesanan ${order.id}? Pesanan bisa dipulihkan lagi dari daftar pesanan terhapus.`)) return
  router.delete(`/admin/pesanan/${order.id}`)
}

const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')

const itemsRows = () =>
  order.items.map((it) => `
    <tr>
      <td>${esc(it.name)}</td>
      <td class="num">${it.qty} × ${formatIDR(it.price)}</td>
      <td class="num">${formatIDR(it.price * it.qty)}</td>
    </tr>`).join('')

const printLabel = () => {
  const win = window.open('', '_blank', 'width=420,height=760')
  if (!win) {
    push('Blokir pop-up browser mencegah cetak label. Izinkan pop-up lalu coba lagi.', { tone: 'danger' })
    return
  }
  win.document.write(`<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Label ${esc(order.id)}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Courier New', monospace; font-size: 12px; color: #111; padding: 20px; }
  h1 { font-size: 14px; letter-spacing: 1px; text-transform: uppercase; }
  .row { display: flex; justify-content: space-between; margin-top: 6px; }
  .box { border: 1.5px solid #111; padding: 12px; margin-top: 10px; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th, td { text-align: left; padding: 5px 4px; border-bottom: 1px solid #999; vertical-align: top; }
  .num { text-align: right; white-space: nowrap; }
  .tot { display: flex; justify-content: space-between; margin-top: 10px; font-weight: bold; }
  .muted { color: #555; }
  .mt { margin-top: 10px; }
</style></head><body>
  <h1>Label pengiriman — ${esc(order.id)}</h1>
  <div class="row"><span class="muted">Tanggal</span><span>${esc(formatDateTime(order.date))}</span></div>
  <div class="row"><span class="muted">Tipe</span><span>${esc(fulfillmentMeta(order.fulfillment).label)}</span></div>
  <div class="row"><span class="muted">Status</span><span>${esc(order.status)} · ${esc(paymentStatusMeta(order.paymentStatus).label)}</span></div>
  <div class="box">
    <b>${isPickup ? 'Ambil di toko' : 'Dikirim ke'}</b>
    <p class="mt">${esc(order.customer)}</p>
    <p>${esc(order.phone)}</p>
    <p class="mt">${esc(isPickup ? (order.shipping.method || 'Ambil di toko ArafahGift') : order.address)}</p>
  </div>
  <div class="row mt"><span><b>Resi</b> ${esc(order.shipping.awb || '—')}</span><span><b>Kurir</b> ${esc(order.shipping.courier)}</span></div>
  <table>
    <thead><tr><th>Barang</th><th class="num">Jml</th><th class="num">Subtotal</th></tr></thead>
    <tbody>${itemsRows()}</tbody>
  </table>
  <div class="tot"><span>Total</span><span>${formatIDR(orderTotal(order))}</span></div>
  <p class="muted mt" style="text-align:center">— ArafahGift.id —</p>
</body></html>`)
  win.document.close()
  win.focus()
  setTimeout(() => { win.print() }, 300)
}
</script>

<template>
  <div v-if="order">
    <Link href="/admin/pesanan" class="inline-flex items-center gap-2 text-[0.8rem] text-muted transition hover:text-forest">
      <ArrowLeft class="h-3.5 w-3.5" /> Semua pesanan
    </Link>

    <header class="mt-5 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">{{ order.id }}</h1>
        <p class="mt-3 text-[0.85rem] text-muted">{{ formatDateTime(order.date) }} · via {{ order.channel }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2.5">
        <span class="rounded-full border border-line bg-ivory px-3 py-1 text-[0.72rem] font-semibold text-forest">{{ fulfillmentMeta(order.fulfillment).label }}</span>
        <span
          class="rounded-full border px-3 py-1 text-[0.72rem] font-semibold"
          :class="paymentPillClass(order.paymentStatus)"
        >{{ paymentStatusMeta(order.paymentStatus).label }}</span>
        <StatusPill :status="order.status" />
        <AppButton variant="quiet" size="sm" @click="printLabel">
          <template #icon><Printer class="h-3.5 w-3.5" /></template>
          Cetak label
        </AppButton>
        <AppButton :href="`/admin/pesanan/${order.id}/invoice`" variant="quiet" size="sm" target="_blank" rel="noopener">
          <template #icon><FileText class="h-3.5 w-3.5" /></template>
          Invoice
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
          <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd>{{ formatIDR(order.items.reduce((s, i) => s + i.price * i.qty, 0)) }}</dd></div>
          <div v-if="order.discount > 0" class="flex justify-between"><dt class="text-muted">Diskon<span v-if="order.voucherCode"> ({{ order.voucherCode }})</span></dt><dd class="text-olive">-{{ formatIDR(order.discount) }}</dd></div>
          <div class="flex justify-between"><dt class="text-muted">{{ isPickup ? 'Pengambilan' : `Ongkir · ${order.shipping.method}` }}</dt><dd>{{ isPickup ? 'Gratis' : formatIDR(order.shipping.cost) }}</dd></div>
          <div class="flex justify-between border-t border-line pt-3 text-[1rem]">
            <dt class="text-forest">Total</dt>
            <dd class="font-display text-2xl text-forest">{{ formatIDR(orderTotal(order)) }}</dd>
          </div>
        </dl>
      </section>

      <div class="space-y-6">
        <section class="border border-line bg-surface p-6">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-2xl">Pembayaran</h2>
            <span
              class="rounded-full border px-2.5 py-0.5 text-[0.7rem] font-semibold"
              :class="paymentPillClass(order.paymentStatus)"
            >{{ paymentStatusMeta(order.paymentStatus).label }}</span>
          </div>
          <p class="mt-4 text-[0.9rem] font-semibold text-forest">{{ order.payment }}</p>
          <p v-if="order.paymentNote" class="mt-0.5 text-[0.75rem] text-muted">{{ order.paymentNote }}</p>

          <div class="mt-4 space-y-2 border-y border-line py-3 text-[0.8rem]">
            <div v-if="order.paymentDueAt" class="flex items-center justify-between gap-3">
              <span class="text-muted">Batas bayar</span>
              <span :class="isOverdue ? 'font-semibold text-danger' : 'text-forest'">{{ formatDateTime(order.paymentDueAt) }}</span>
            </div>
            <div v-if="order.paymentConfirmedAt" class="flex items-center justify-between gap-3">
              <span class="text-muted">Dikonfirmasi</span>
              <span class="text-forest">{{ formatDateTime(order.paymentConfirmedAt) }}</span>
            </div>
            <p v-if="isOverdue" class="flex items-start gap-2 border border-danger/30 bg-danger/5 p-2 text-[0.75rem] leading-relaxed text-danger">
              <ShieldCheck class="mt-0.5 h-3.5 w-3.5 flex-none" :stroke-width="1.5" />
              Batas bayar sudah lewat. Pesanan otomatis dibatalkan bila pelanggan belum membayar.
            </p>
          </div>

          <label class="field-label mt-4" for="paymentReference">Referensi / berita transfer</label>
          <input id="paymentReference" v-model="paymentReference" class="field" maxlength="100" placeholder="Contoh: BSI 123456789 a.n. Budi" />

          <div v-if="paymentProofs.length" class="mt-4 space-y-3">
            <p class="text-[0.7rem] uppercase tracking-[0.12em] text-muted">Bukti pembayaran ({{ paymentProofs.length }})</p>
            <article v-for="proof in paymentProofs" :key="proof.id" class="border border-line bg-ivory/40 p-3">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0">
                  <a
                    :href="proof.url" target="_blank" rel="noopener"
                    class="inline-flex max-w-full items-center gap-1.5 text-[0.82rem] font-medium text-forest underline underline-offset-4"
                  >
                    <FileText class="h-3.5 w-3.5 flex-none" :stroke-width="1.5" />
                    <span class="truncate">{{ proof.fileName }}</span>
                    <ExternalLink class="h-3 w-3 flex-none" :stroke-width="1.5" />
                  </a>
                  <p class="mt-1 text-[0.72rem] text-muted">
                    {{ formatDateTime(proof.submittedAt) }}
                    <span v-if="proof.amount"> · {{ formatIDR(proof.amount) }}</span>
                    <span v-if="proof.senderName"> · a.n. {{ proof.senderName }}</span>
                  </p>
                  <p v-if="proof.note" class="mt-1 text-[0.75rem] text-muted">{{ proof.note }}</p>
                </div>
                <span
                  class="rounded-full border px-2 py-0.5 text-[0.66rem] font-semibold uppercase tracking-[0.06em]"
                  :class="proof.status === 'verified'
                    ? 'border-olive/40 bg-olive/10 text-olive'
                    : proof.status === 'rejected'
                      ? 'border-danger/30 bg-danger/10 text-danger'
                      : 'border-sky-300 bg-sky-50 text-sky-800'"
                >{{ proof.statusLabel }}</span>
              </div>

              <p v-if="proof.rejectionReason" class="mt-2 text-[0.75rem] text-danger">Alasan: {{ proof.rejectionReason }}</p>
              <p v-if="proof.reviewer && proof.reviewedAt" class="mt-1 text-[0.7rem] text-muted">Ditinjau {{ proof.reviewer }} · {{ formatDateTime(proof.reviewedAt) }}</p>

              <div v-if="proof.status === 'submitted' && isWaitingVerification" class="mt-3">
                <div v-if="rejectingProofId !== proof.id" class="flex flex-wrap gap-2">
                  <AppButton size="sm" @click="verifyProof(proof)">
                    <template #icon><Check class="h-3.5 w-3.5" /></template>
                    Verifikasi &amp; lunas
                  </AppButton>
                  <AppButton size="sm" variant="outline" @click="startReject(proof)">
                    <template #icon><X class="h-3.5 w-3.5" /></template>
                    Tolak
                  </AppButton>
                </div>

                <div v-else class="border border-danger/30 bg-danger/5 p-3">
                  <label class="field-label" :for="`reject-${proof.id}`">Alasan penolakan</label>
                  <textarea
                    :id="`reject-${proof.id}`"
                    v-model="rejectionReason"
                    rows="2"
                    maxlength="255"
                    class="field"
                    placeholder="Contoh: nominal transfer tidak sesuai total pesanan."
                  />
                  <p class="mt-1 text-[0.72rem] text-muted">Alasan ini akan terlihat pelanggan di halaman lacak pesanan.</p>
                  <div class="mt-2 flex flex-wrap gap-2">
                    <AppButton
                      size="sm"
                      variant="outline"
                      :loading="rejectionProcessing"
                      :disabled="!rejectionReason.trim()"
                      @click="rejectProof(proof)"
                    >Kirim penolakan</AppButton>
                    <AppButton size="sm" variant="quiet" :disabled="rejectionProcessing" @click="cancelReject">Batal</AppButton>
                  </div>
                </div>
              </div>
            </article>
          </div>
          <p v-else class="mt-4 text-[0.78rem] text-muted">Belum ada bukti pembayaran yang diunggah pelanggan.</p>

          <p v-if="order.paymentInstructions" class="mt-4 whitespace-pre-line border border-line bg-ivory/50 px-3 py-2.5 text-[0.78rem] leading-relaxed text-muted">{{ order.paymentInstructions }}</p>

          <div class="mt-4 flex flex-wrap gap-2">
            <AppButton
              v-for="s in manualPaymentStatuses" :key="s.id" size="sm"
              :variant="order.paymentStatus === s.id ? 'primary' : 'outline'"
              :disabled="order.paymentStatus === s.id"
              @click="markPayment(s.id)"
            >{{ s.label }}</AppButton>
          </div>

          <a
            :href="customerWaHref" target="_blank" rel="noopener"
            class="mt-3 inline-flex items-center gap-1.5 text-[0.78rem] text-forest underline underline-offset-4"
          >
            <MessageCircle class="h-3.5 w-3.5" :stroke-width="1.5" /> Chat pelanggan via WhatsApp
          </a>
        </section>

        <section v-if="order.events?.length" class="border border-line bg-surface p-6">
          <h2 class="font-display text-2xl">Riwayat</h2>
          <ol class="mt-4 space-y-3">
            <li v-for="event in order.events" :key="event.id" class="flex gap-3 text-[0.8rem]">
              <span class="mt-1.5 h-1.5 w-1.5 flex-none rotate-45 bg-gold" />
              <div class="min-w-0">
                <p class="text-forest">
                  {{ event.label }}
                  <span v-if="event.from || event.to" class="text-muted">· {{ orderValueLabel(event.from) }} → {{ orderValueLabel(event.to) }}</span>
                </p>
                <p class="text-[0.72rem] text-muted">{{ formatDateTime(event.at) }} · {{ event.actor }}</p>
                <p v-if="event.meta?.reason" class="mt-0.5 text-[0.72rem] text-muted">{{ event.meta.reason }}</p>
              </div>
            </li>
          </ol>
        </section>

        <section class="border border-line bg-surface p-6">
          <h2 class="font-display text-2xl">Ubah status</h2>
          <label class="field-label mt-5" for="status">Status pesanan</label>
          <select id="status" v-model="form.status" class="field">
            <option v-for="s in orderStatuses" :key="s.id" :value="s.id">{{ s.label }}</option>
          </select>
          <label class="field-label mt-4" for="awb">Nomor resi</label>
          <input id="awb" v-model="form.awb" class="field" placeholder="Belum ada resi" />
          <label class="field-label mt-4" for="shippingCost">Ongkir (Rp)</label>
          <input id="shippingCost" v-model.number="form.shippingCost" type="number" min="0" class="field" placeholder="0" />
          <label class="field-label mt-4" for="adminNote">Catatan admin</label>
          <textarea id="adminNote" v-model="form.adminNote" rows="3" class="field" placeholder="Catatan internal, tidak terlihat pembeli" />
          <AppButton block class="mt-5" :loading="form.processing" @click="save">Simpan perubahan</AppButton>
          <button type="button" class="mt-4 flex w-full items-center justify-center gap-1.5 text-[0.78rem] text-muted underline underline-offset-4 transition hover:text-danger" @click="destroyOrder">
            <Trash2 class="h-3.5 w-3.5" /> Hapus pesanan
          </button>
        </section>

        <section class="border border-line bg-surface p-6 text-[0.85rem] leading-relaxed text-muted">
          <h2 class="font-display text-2xl text-forest">Pelanggan</h2>
          <p class="mt-4 text-forest">{{ order.customer }}</p>
          <p>{{ order.email }}</p>
          <p>{{ order.phone }}</p>
          <p class="mt-4 border-t border-line pt-4">{{ isPickup ? 'Ambil di toko · tanpa ongkir' : order.address }}</p>
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
