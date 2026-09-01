<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Printer } from 'lucide-vue-next'
import StatusPill from '@/components/admin/StatusPill.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { orderStatuses, orderTotal } from '@/data/admin'
import { formatDateTime, formatIDR } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'

const props = defineProps({ order: { type: Object, default: null } })
const { push } = useToast()
const order = props.order

const form = useForm({
  status: order?.status ?? 'pending',
  awb: order?.shipping.awb ?? '',
  shippingCost: order?.shipping.cost ?? 0,
  adminNote: order?.admin_note ?? '',
})

const save = () => {
  form.put(`/admin/pesanan/${order.id}`, {
    preserveScroll: true,
    onSuccess: () => push(`Status ${order.id} diperbarui`, { tone: 'success' }),
  })
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
  <div class="row"><span class="muted">Status</span><span>${esc(order.status)}</span></div>
  <div class="box">
    <b>Dikirim ke</b>
    <p class="mt">${esc(order.customer)}</p>
    <p>${esc(order.phone)}</p>
    <p class="mt">${esc(order.address)}</p>
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
      <div class="flex items-center gap-3">
        <StatusPill :status="order.status" />
        <AppButton variant="quiet" size="sm" @click="printLabel">
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
