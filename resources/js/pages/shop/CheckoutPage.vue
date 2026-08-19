<script>
import BareLayout from '@/layouts/BareLayout.vue'
export default { layout: BareLayout }
</script>

<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Check, Gift, Lock } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import BrandLogo from '@/components/storefront/BrandLogo.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { formatIDR } from '@/composables/useFormat'
import { useCart } from '@/composables/useCart'

const props = defineProps({
  shippingMethods: { type: Array, required: true },
  paymentMethods: { type: Array, required: true },
})

const cart = useCart()

const steps = [
  { id: 1, label: 'Data pemesan' },
  { id: 2, label: 'Pengiriman' },
  { id: 3, label: 'Pembayaran' },
  { id: 4, label: 'Tinjau' },
]
const step = ref(1)
const submitting = ref(false)
const errors = reactive({})

const form = reactive({
  name: '', email: '', phone: '',
  address: '', city: '', province: '', postal: '',
  shipping: 'reguler', payment: 'transfer',
  giftMessage: '', hideInvoice: true,
})

const shipping = computed(() => props.shippingMethods.find((s) => s.id === form.shipping))
const total = computed(() => cart.subtotal.value + (shipping.value?.price ?? 0))

const validate = (current) => {
  Object.keys(errors).forEach((k) => delete errors[k])
  if (current === 1) {
    if (form.name.trim().length < 3) errors.name = 'Tulis nama lengkap penerima pesanan.'
    if (!form.email.includes('@')) errors.email = 'Format email belum benar, contoh nama@email.com.'
    if (form.phone.replace(/\D/g, '').length < 9) errors.phone = 'Nomor WhatsApp minimal 9 angka.'
  }
  if (current === 2) {
    if (form.address.trim().length < 8) errors.address = 'Tulis alamat lengkap termasuk nomor rumah.'
    if (!form.city.trim()) errors.city = 'Isi kota atau kabupaten.'
    if (!/^\d{5}$/.test(form.postal)) errors.postal = 'Kode pos terdiri dari 5 angka.'
  }
  return Object.keys(errors).length === 0
}

const next = () => { if (validate(step.value)) step.value = Math.min(4, step.value + 1) }
const back = () => { step.value = Math.max(1, step.value - 1) }

const placeOrder = () => {
  submitting.value = true
  router.post('/checkout', {
    ...form,
    items: cart.items.value.map((item) => ({ product_id: item.id, qty: item.qty })),
  }, {
    onSuccess: () => cart.clear(),
    onError: (serverErrors) => Object.assign(errors, serverErrors),
    onFinish: () => { submitting.value = false },
  })
}
</script>

<template>
  <div class="min-h-screen bg-surface">
    <div v-if="cart.items.value.length" class="mx-auto grid max-w-6xl lg:grid-cols-[1.2fr_1fr]">
      <!-- Form -->
      <div class="px-5 py-10 sm:px-10 lg:py-14">
        <div class="flex items-center justify-between">
          <BrandLogo size="sm" />
          <Link href="/keranjang" class="flex items-center gap-1.5 text-[0.78rem] text-muted transition hover:text-forest">
            <ArrowLeft class="h-3.5 w-3.5" /> Kembali ke keranjang
          </Link>
        </div>

        <!-- Stepper: urutan checkout benar-benar berurutan -->
        <ol class="mt-10 flex items-center gap-3">
          <li v-for="s in steps" :key="s.id" class="flex flex-1 items-center gap-3">
            <span
              class="grid h-7 w-7 flex-none place-items-center rounded-full border text-[0.72rem] transition"
              :class="step > s.id ? 'border-forest bg-forest text-ivory'
                : step === s.id ? 'border-gold bg-gold/15 text-forest' : 'border-line text-muted'"
            >
              <Check v-if="step > s.id" class="h-3.5 w-3.5" />
              <template v-else>{{ s.id }}</template>
            </span>
            <span class="hidden text-[0.75rem] tracking-wide sm:block" :class="step >= s.id ? 'text-forest' : 'text-muted'">{{ s.label }}</span>
            <span v-if="s.id < 4" class="h-px flex-1 bg-line" />
          </li>
        </ol>

        <!-- 1. Data pemesan -->
        <section v-if="step === 1" class="mt-10">
          <h1 class="text-[1.9rem] leading-none">Data pemesan</h1>
          <p class="mt-3 text-[0.85rem] text-muted">Kami pakai ini untuk mengirim status pesanan.</p>
          <div class="mt-8 space-y-5">
            <div>
              <label class="field-label" for="name">Nama lengkap</label>
              <input id="name" v-model="form.name" class="field" placeholder="Nama Anda" :aria-invalid="!!errors.name" />
              <p v-if="errors.name" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.name }}</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <label class="field-label" for="email">Email</label>
                <input id="email" v-model="form.email" type="email" class="field" placeholder="nama@email.com" :aria-invalid="!!errors.email" />
                <p v-if="errors.email" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.email }}</p>
              </div>
              <div>
                <label class="field-label" for="phone">Nomor WhatsApp</label>
                <input id="phone" v-model="form.phone" class="field" placeholder="08xx xxxx xxxx" :aria-invalid="!!errors.phone" />
                <p v-if="errors.phone" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.phone }}</p>
              </div>
            </div>
          </div>
        </section>

        <!-- 2. Pengiriman -->
        <section v-else-if="step === 2" class="mt-10">
          <h1 class="text-[1.9rem] leading-none">Pengiriman</h1>
          <p class="mt-3 text-[0.85rem] text-muted">Bisa dikirim langsung ke alamat penerima hadiah.</p>
          <div class="mt-8 space-y-5">
            <div>
              <label class="field-label" for="address">Alamat lengkap</label>
              <textarea id="address" v-model="form.address" rows="3" class="field" placeholder="Nama jalan, nomor rumah, RT/RW, patokan" />
              <p v-if="errors.address" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.address }}</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-3">
              <div class="sm:col-span-2">
                <label class="field-label" for="city">Kota / kabupaten</label>
                <input id="city" v-model="form.city" class="field" placeholder="Contoh: Parepare" />
                <p v-if="errors.city" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.city }}</p>
              </div>
              <div>
                <label class="field-label" for="postal">Kode pos</label>
                <input id="postal" v-model="form.postal" inputmode="numeric" maxlength="5" class="field" placeholder="91114" />
                <p v-if="errors.postal" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.postal }}</p>
              </div>
            </div>

            <fieldset class="pt-2">
              <legend class="field-label">Metode pengiriman</legend>
              <div class="mt-2 divide-y divide-line border border-line">
                <label
                  v-for="m in shippingMethods" :key="m.id"
                  class="flex cursor-pointer items-center gap-4 px-4 py-3.5 transition"
                  :class="form.shipping === m.id ? 'bg-ivory' : 'hover:bg-ivory/60'"
                >
                  <input v-model="form.shipping" type="radio" :value="m.id" class="h-3.5 w-3.5 accent-[rgb(var(--c-forest))]" />
                  <span class="flex-1 text-[0.87rem] text-forest">{{ m.name }}<span class="ml-2 text-[0.75rem] text-muted">{{ m.eta }}</span></span>
                  <span class="text-[0.85rem] text-forest">{{ formatIDR(m.price) }}</span>
                </label>
              </div>
            </fieldset>
          </div>
        </section>

        <!-- 3. Pembayaran -->
        <section v-else-if="step === 3" class="mt-10">
          <h1 class="text-[1.9rem] leading-none">Pembayaran</h1>
          <p class="mt-3 text-[0.85rem] text-muted">Instruksi lengkap dikirim ke email setelah pesanan dibuat.</p>
          <div class="mt-8 divide-y divide-line border border-line">
            <label
              v-for="m in paymentMethods" :key="m.id"
              class="flex cursor-pointer items-center gap-4 px-4 py-4 transition"
              :class="form.payment === m.id ? 'bg-ivory' : 'hover:bg-ivory/60'"
            >
              <input v-model="form.payment" type="radio" :value="m.id" class="h-3.5 w-3.5 accent-[rgb(var(--c-forest))]" />
              <span class="flex-1">
                <span class="block text-[0.9rem] text-forest">{{ m.name }}</span>
                <span class="block text-[0.75rem] text-muted">{{ m.note }}</span>
              </span>
            </label>
          </div>

          <div class="mt-8 border border-dashed border-gold/40 bg-gold/[0.07] p-5">
            <div class="flex items-center gap-2.5">
              <Gift class="h-4 w-4 text-gold" :stroke-width="1.5" />
              <h2 class="font-display text-xl text-forest">Kartu ucapan gratis</h2>
            </div>
            <label class="sr-only" for="giftmsg">Pesan kartu ucapan</label>
            <textarea
              id="giftmsg" v-model="form.giftMessage" rows="3" maxlength="180"
              class="field mt-4 bg-surface" placeholder="Contoh: Untuk Ibu, dari tanah suci. Doa kami sampai duluan."
            />
            <div class="mt-2 flex items-center justify-between">
              <label class="flex items-center gap-2 text-[0.78rem] text-forest">
                <input v-model="form.hideInvoice" type="checkbox" class="h-3.5 w-3.5 accent-[rgb(var(--c-forest))]" />
                Sembunyikan nota harga di dalam paket
              </label>
              <span class="text-[0.72rem] text-muted">{{ form.giftMessage.length }}/180</span>
            </div>
          </div>
        </section>

        <!-- 4. Tinjau -->
        <section v-else class="mt-10">
          <h1 class="text-[1.9rem] leading-none">Tinjau pesanan</h1>
          <p class="mt-3 text-[0.85rem] text-muted">Periksa sekali lagi sebelum pesanan dibuat.</p>
          <dl class="mt-8 divide-y divide-line border-y border-line text-[0.87rem]">
            <div class="flex justify-between gap-6 py-4">
              <dt class="text-muted">Pemesan</dt>
              <dd class="text-right text-forest">{{ form.name }}<span class="block text-[0.78rem] text-muted">{{ form.email }} · {{ form.phone }}</span></dd>
            </div>
            <div class="flex justify-between gap-6 py-4">
              <dt class="text-muted">Kirim ke</dt>
              <dd class="max-w-xs text-right text-forest">{{ form.address }}, {{ form.city }} {{ form.postal }}</dd>
            </div>
            <div class="flex justify-between gap-6 py-4">
              <dt class="text-muted">Pengiriman</dt>
              <dd class="text-right text-forest">{{ shipping.name }} · {{ shipping.eta }}</dd>
            </div>
            <div class="flex justify-between gap-6 py-4">
              <dt class="text-muted">Pembayaran</dt>
              <dd class="text-right text-forest">{{ paymentMethods.find((p) => p.id === form.payment).name }}</dd>
            </div>
            <div v-if="form.giftMessage" class="flex justify-between gap-6 py-4">
              <dt class="text-muted">Kartu ucapan</dt>
              <dd class="max-w-xs text-right font-display text-[1.05rem] italic text-forest">"{{ form.giftMessage }}"</dd>
            </div>
          </dl>
        </section>

        <div class="mt-10 flex items-center gap-3">
          <AppButton v-if="step > 1" variant="quiet" size="lg" @click="back">Kembali</AppButton>
          <AppButton v-if="step < 4" size="lg" class="flex-1 sm:flex-none sm:min-w-[12rem]" @click="next">Lanjut</AppButton>
          <AppButton v-else size="lg" class="flex-1 sm:flex-none sm:min-w-[14rem]" :loading="submitting" @click="placeOrder">
            Buat pesanan · {{ formatIDR(total) }}
          </AppButton>
        </div>
        <p class="mt-5 flex items-center gap-2 text-[0.72rem] text-muted">
          <Lock class="h-3.5 w-3.5 text-gold" /> Data Anda hanya dipakai untuk memproses pesanan ini.
        </p>
      </div>

      <!-- Ringkasan -->
      <aside class="border-t border-line bg-ivory px-5 py-10 sm:px-10 lg:sticky lg:top-0 lg:h-screen lg:overflow-y-auto lg:border-l lg:border-t-0 lg:py-14">
        <h2 class="font-display text-2xl">Ringkasan pesanan</h2>
        <ul class="mt-7 space-y-5">
          <li v-for="item in cart.items.value" :key="item.id" class="flex gap-4">
            <div class="relative flex-none">
              <span class="arch block h-20 w-16 overflow-hidden border border-line"><ProductArt :art="item.art" :tone="item.id" /></span>
              <span class="absolute -right-2 -top-2 grid h-6 w-6 place-items-center rounded-full bg-forest text-[0.68rem] text-ivory">{{ item.qty }}</span>
            </div>
            <div class="flex-1">
              <p class="font-display text-[1.1rem] leading-tight text-forest">{{ item.name }}</p>
              <p class="mt-1 text-[0.75rem] text-muted">{{ item.category }}</p>
            </div>
            <p class="text-[0.85rem] text-forest">{{ formatIDR(item.lineTotal) }}</p>
          </li>
        </ul>

        <dl class="mt-8 space-y-3 border-t border-line pt-6 text-[0.87rem]">
          <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="text-forest">{{ formatIDR(cart.subtotal.value) }}</dd></div>
          <div class="flex justify-between"><dt class="text-muted">Ongkos kirim · {{ shipping.name }}</dt><dd class="text-forest">{{ formatIDR(shipping.price) }}</dd></div>
          <div class="flex justify-between"><dt class="text-muted">Kartu ucapan</dt><dd class="text-olive">Gratis</dd></div>
        </dl>
        <div class="mt-6 flex items-baseline justify-between border-t border-line pt-5">
          <span class="text-[0.85rem] text-muted">Total</span>
          <span class="font-display text-3xl text-forest">{{ formatIDR(total) }}</span>
        </div>
      </aside>
    </div>

    <div v-else class="shell py-24">
      <EmptyState title="Belum ada yang bisa dibayar" body="Keranjang Anda kosong. Pilih dulu hadiahnya, lalu kembali ke sini.">
        <AppButton to="/koleksi">Jelajahi koleksi</AppButton>
      </EmptyState>
    </div>
  </div>
</template>
