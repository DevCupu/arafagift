<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({ settings: { type: Object, required: true } })
const { push } = useToast()

const form = useForm({
  store_name: props.settings.store_name,
  tagline: props.settings.tagline ?? '',
  email: props.settings.email ?? '',
  whatsapp: props.settings.whatsapp ?? '',
  address: props.settings.address ?? '',
  free_shipping_from: props.settings.free_shipping_from,
  free_shipping_cities: props.settings.free_shipping_cities ?? '',
  bulk_minimum: props.settings.bulk_minimum,
})

const save = () => {
  form.put('/admin/pengaturan', {
    preserveScroll: true,
    onSuccess: () => push('Pengaturan toko disimpan', { tone: 'success' }),
  })
}
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Pengaturan</h1>
        <p class="mt-3 text-[0.85rem] text-muted">Identitas toko dan aturan dasar penjualan.</p>
      </div>
      <AppButton size="sm" :loading="form.processing" @click="save">Simpan pengaturan</AppButton>
    </header>

    <div class="mt-8 grid max-w-4xl gap-6 lg:grid-cols-2">
      <section class="border border-line bg-surface p-6 sm:p-7">
        <h2 class="font-display text-2xl">Identitas toko</h2>
        <div class="mt-6 space-y-5">
          <div>
            <label class="field-label" for="s-name">Nama toko</label>
            <input id="s-name" v-model="form.store_name" class="field" />
            <p v-if="form.errors.store_name" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.store_name }}</p>
          </div>
          <div>
            <label class="field-label" for="s-tagline">Tagline</label>
            <input id="s-tagline" v-model="form.tagline" class="field" />
          </div>
          <div>
            <label class="field-label" for="s-address">Alamat toko</label>
            <textarea id="s-address" v-model="form.address" rows="3" class="field" />
          </div>
        </div>
      </section>

      <section class="border border-line bg-surface p-6 sm:p-7">
        <h2 class="font-display text-2xl">Kontak</h2>
        <div class="mt-6 space-y-5">
          <div>
            <label class="field-label" for="s-email">Email</label>
            <input id="s-email" v-model="form.email" type="email" class="field" />
          </div>
          <div>
            <label class="field-label" for="s-wa">Nomor WhatsApp</label>
            <input id="s-wa" v-model="form.whatsapp" class="field" placeholder="+62 812-3456-7890" />
            <p class="mt-1.5 text-[0.72rem] text-muted">Nomor ini dipakai untuk tombol "Pesan via WhatsApp" di checkout.</p>
          </div>
        </div>
      </section>

      <section class="border border-line bg-surface p-6 sm:p-7 lg:col-span-2">
        <h2 class="font-display text-2xl">Aturan penjualan</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
          <div>
            <label class="field-label" for="s-free">Gratis ongkir mulai (Rp)</label>
            <input id="s-free" v-model="form.free_shipping_from" inputmode="numeric" class="field" />
            <p class="mt-1.5 text-[0.72rem] text-muted">Total belanja minimum supaya dapat gratis ongkir, di kota mana pun.</p>
          </div>
          <div>
            <label class="field-label" for="s-bulk">Minimum pesanan rombongan (pcs)</label>
            <input id="s-bulk" v-model="form.bulk_minimum" inputmode="numeric" class="field" />
          </div>
          <div class="sm:col-span-2">
            <label class="field-label" for="s-cities">Kota gratis ongkir</label>
            <input id="s-cities" v-model="form.free_shipping_cities" class="field" placeholder="Makassar, Jakarta Selatan" />
            <p class="mt-1.5 text-[0.72rem] text-muted">Pisahkan dengan koma. Pembeli dari kota ini selalu dapat gratis ongkir, berapa pun totalnya.</p>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
