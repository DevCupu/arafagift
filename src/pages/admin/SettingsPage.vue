<script setup>
import { reactive, ref } from 'vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'

const { push } = useToast()
const saving = ref(false)
const form = reactive({
  storeName: 'ArafahGift.id',
  tagline: 'Oleh-oleh Umrah & Hajj yang dipilih dengan hati.',
  email: 'halo@arafahgift.id',
  whatsapp: '+62 812-3456-7890',
  address: 'Jl. Cikini Raya 45, Jakarta Pusat 10330',
  freeShippingFrom: 750000,
  bulkMinimum: 50,
})

const save = () => {
  saving.value = true
  setTimeout(() => { saving.value = false; push('Pengaturan toko disimpan', { tone: 'success' }) }, 700)
}
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Pengaturan</h1>
        <p class="mt-3 text-[0.85rem] text-muted">Identitas toko dan aturan dasar penjualan.</p>
      </div>
      <AppButton size="sm" :loading="saving" @click="save">Simpan pengaturan</AppButton>
    </header>

    <div class="mt-8 grid max-w-4xl gap-6 lg:grid-cols-2">
      <section class="border border-line bg-surface p-6 sm:p-7">
        <h2 class="font-display text-2xl">Identitas toko</h2>
        <div class="mt-6 space-y-5">
          <div>
            <label class="field-label" for="s-name">Nama toko</label>
            <input id="s-name" v-model="form.storeName" class="field" />
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
            <input id="s-wa" v-model="form.whatsapp" class="field" />
          </div>
        </div>
      </section>

      <section class="border border-line bg-surface p-6 sm:p-7 lg:col-span-2">
        <h2 class="font-display text-2xl">Aturan penjualan</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
          <div>
            <label class="field-label" for="s-free">Gratis ongkir mulai (Rp)</label>
            <input id="s-free" v-model="form.freeShippingFrom" inputmode="numeric" class="field" />
          </div>
          <div>
            <label class="field-label" for="s-bulk">Minimum pesanan rombongan (pcs)</label>
            <input id="s-bulk" v-model="form.bulkMinimum" inputmode="numeric" class="field" />
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
