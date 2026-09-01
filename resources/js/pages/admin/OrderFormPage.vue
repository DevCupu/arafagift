<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Plus, X } from 'lucide-vue-next'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { orderStatuses } from '@/data/admin'
import { formatIDR } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'

const props = defineProps({ products: { type: Array, required: true } })
const { push } = useToast()

const form = useForm({
  name: '',
  phone: '',
  email: '',
  address: '',
  city: '',
  province: '',
  postal: '',
  status: 'pending',
  shippingCost: 0,
  note: '',
  adminNote: '',
})

// ── Produk pesanan ──
const productToAdd = ref(props.products[0]?.id ?? null)
const qtyToAdd = ref(1)
const orderItems = ref([])

const addItem = () => {
  const product = props.products.find((p) => p.id === productToAdd.value)
  if (!product || qtyToAdd.value < 1) return

  const existing = orderItems.value.find((i) => i.id === product.id)
  if (existing) {
    existing.qty += qtyToAdd.value
  } else {
    orderItems.value.push({ id: product.id, name: product.name, sku: product.sku, price: product.price, stock: product.stock, art: product.art, qty: qtyToAdd.value })
  }
  qtyToAdd.value = 1
}
const removeItem = (id) => { orderItems.value = orderItems.value.filter((i) => i.id !== id) }

const subtotal = computed(() => orderItems.value.reduce((sum, i) => sum + i.price * i.qty, 0))
const total = computed(() => subtotal.value + (Number(form.shippingCost) || 0))

const save = () => {
  if (!orderItems.value.length) {
    push('Tambahkan minimal satu produk', { tone: 'danger' })
    return
  }
  form
    .transform((data) => ({ ...data, items: orderItems.value.map((i) => ({ id: i.id, qty: i.qty })) }))
    .post('/admin/pesanan', {
      onSuccess: () => push('Pesanan baru dibuat', { tone: 'success' }),
    })
}
</script>

<template>
  <div>
    <Link href="/admin/pesanan" class="inline-flex items-center gap-2 text-[0.8rem] text-muted transition hover:text-forest">
      <ArrowLeft class="h-3.5 w-3.5" /> Semua pesanan
    </Link>

    <header class="mt-5">
      <h1 class="text-[2.1rem] leading-none">Pesanan baru</h1>
      <p class="mt-3 text-[0.85rem] text-muted">Untuk pesanan yang masuk lewat WhatsApp atau telepon, dicatat manual di sini.</p>
    </header>

    <div class="mt-8 grid gap-6 xl:grid-cols-[1.4fr_1fr]">
      <section class="space-y-6">
        <div class="border border-line bg-surface p-6">
          <h2 class="font-display text-xl">Data pelanggan</h2>
          <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div>
              <label class="field-label" for="o-name">Nama lengkap</label>
              <input id="o-name" v-model="form.name" class="field" />
              <p v-if="form.errors.name" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.name }}</p>
            </div>
            <div>
              <label class="field-label" for="o-phone">Nomor WhatsApp</label>
              <input id="o-phone" v-model="form.phone" class="field" />
              <p v-if="form.errors.phone" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.phone }}</p>
            </div>
          </div>
          <div class="mt-5">
            <label class="field-label" for="o-email">Email (opsional)</label>
            <input id="o-email" v-model="form.email" type="email" class="field" />
          </div>
        </div>

        <div class="border border-line bg-surface p-6">
          <h2 class="font-display text-xl">Alamat pengiriman</h2>
          <div class="mt-5">
            <label class="field-label" for="o-address">Alamat lengkap</label>
            <textarea id="o-address" v-model="form.address" rows="3" class="field" />
            <p v-if="form.errors.address" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.address }}</p>
          </div>
          <div class="mt-5 grid gap-5 sm:grid-cols-3">
            <div class="sm:col-span-2">
              <label class="field-label" for="o-city">Kota / kabupaten</label>
              <input id="o-city" v-model="form.city" class="field" />
              <p v-if="form.errors.city" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.city }}</p>
            </div>
            <div>
              <label class="field-label" for="o-postal">Kode pos</label>
              <input id="o-postal" v-model="form.postal" class="field" />
            </div>
          </div>
          <div class="mt-5">
            <label class="field-label" for="o-province">Provinsi (opsional)</label>
            <input id="o-province" v-model="form.province" class="field sm:w-72" />
          </div>
        </div>

        <div class="border border-line bg-surface p-6">
          <h2 class="font-display text-xl">Produk</h2>
          <div class="mt-5 flex flex-wrap items-end gap-3">
            <div class="flex-1">
              <label class="field-label" for="o-product">Produk</label>
              <select id="o-product" v-model.number="productToAdd" class="field">
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} · {{ formatIDR(p.price) }} · stok {{ p.stock }}</option>
              </select>
            </div>
            <div class="w-24">
              <label class="field-label" for="o-qty">Jumlah</label>
              <input id="o-qty" v-model.number="qtyToAdd" type="number" min="1" class="field" />
            </div>
            <AppButton size="sm" variant="quiet" type="button" @click="addItem">
              <template #icon><Plus class="h-3.5 w-3.5" /></template>
              Tambah
            </AppButton>
          </div>

          <ul v-if="orderItems.length" class="mt-6 divide-y divide-line border-y border-line">
            <li v-for="item in orderItems" :key="item.id" class="flex items-center gap-4 py-3.5">
              <span class="arch h-12 w-9 flex-none overflow-hidden border border-line bg-ivory"><ProductArt :art="item.art" :tone="item.id" /></span>
              <span class="flex-1 min-w-0 text-[0.87rem] text-forest">{{ item.name }}</span>
              <input v-model.number="item.qty" type="number" min="1" class="field w-20 py-1.5 text-center" />
              <span class="w-28 text-right text-[0.87rem] text-forest">{{ formatIDR(item.price * item.qty) }}</span>
              <button type="button" class="text-muted transition hover:text-danger" @click="removeItem(item.id)"><X class="h-4 w-4" /></button>
              <p v-if="form.errors[`items.${item.id}`]" class="w-full text-[0.72rem] text-danger">{{ form.errors[`items.${item.id}`] }}</p>
            </li>
          </ul>
          <p v-else class="mt-6 text-[0.83rem] text-muted">Belum ada produk ditambahkan.</p>
        </div>

        <div class="border border-line bg-surface p-6">
          <h2 class="font-display text-xl">Status &amp; catatan</h2>
          <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div>
              <label class="field-label" for="o-status">Status pesanan</label>
              <select id="o-status" v-model="form.status" class="field">
                <option v-for="s in orderStatuses" :key="s.id" :value="s.id">{{ s.label }}</option>
              </select>
            </div>
            <div>
              <label class="field-label" for="o-shipping">Ongkir (Rp)</label>
              <input id="o-shipping" v-model.number="form.shippingCost" type="number" min="0" class="field" />
            </div>
          </div>
          <div class="mt-5">
            <label class="field-label" for="o-note">Catatan pesanan (opsional)</label>
            <textarea id="o-note" v-model="form.note" rows="2" class="field" />
          </div>
          <div class="mt-5">
            <label class="field-label" for="o-adminnote">Catatan admin (opsional)</label>
            <textarea id="o-adminnote" v-model="form.adminNote" rows="2" class="field" placeholder="Catatan internal, tidak terlihat pembeli" />
          </div>
        </div>
      </section>

      <aside class="border border-line bg-ivory p-6 xl:sticky xl:top-8 xl:h-fit">
        <h2 class="font-display text-xl">Ringkasan</h2>
        <dl class="mt-5 space-y-2.5 text-[0.87rem]">
          <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="text-forest">{{ formatIDR(subtotal) }}</dd></div>
          <div class="flex justify-between"><dt class="text-muted">Ongkir</dt><dd class="text-forest">{{ formatIDR(Number(form.shippingCost) || 0) }}</dd></div>
          <div class="flex justify-between border-t border-line pt-3 text-[1rem]">
            <dt class="text-forest">Total</dt>
            <dd class="font-display text-2xl text-forest">{{ formatIDR(total) }}</dd>
          </div>
        </dl>
        <AppButton block class="mt-6" :loading="form.processing" @click="save">Buat pesanan</AppButton>
      </aside>
    </div>
  </div>
</template>
