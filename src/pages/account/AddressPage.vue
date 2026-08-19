<script setup>
import { ref } from 'vue'
import { Plus } from 'lucide-vue-next'
import AccountShell from '@/components/storefront/AccountShell.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const addresses = ref([
  { id: 1, label: 'Rumah', name: 'Ratna Halim', phone: '0812-3344-5566', text: 'Jl. Dharmahusada Indah 12, Surabaya, Jawa Timur 60285', primary: true },
  { id: 2, label: 'Rumah Ibu', name: 'Hj. Aminah', phone: '0813-7788-9900', text: 'Jl. Melati 4 RT 02/RW 05, Kediri, Jawa Timur 64114', primary: false },
])
const setPrimary = (id) => addresses.value.forEach((a) => (a.primary = a.id === id))
</script>

<template>
  <AccountShell title="Alamat" sub="Simpan alamat penerima supaya checkout berikutnya lebih cepat.">
    <div class="grid gap-4 sm:grid-cols-2">
      <article v-for="a in addresses" :key="a.id" class="border border-line bg-surface p-6">
        <div class="flex items-center justify-between">
          <p class="text-[0.72rem] uppercase tracking-[0.14em] text-olive">{{ a.label }}</p>
          <AppBadge v-if="a.primary">Utama</AppBadge>
        </div>
        <p class="mt-4 font-display text-xl text-forest">{{ a.name }}</p>
        <p class="mt-2 text-[0.85rem] leading-relaxed text-muted">{{ a.text }}</p>
        <p class="mt-2 text-[0.85rem] text-muted">{{ a.phone }}</p>
        <div class="mt-5 flex gap-3">
          <button class="text-[0.8rem] text-forest underline underline-offset-4">Ubah</button>
          <button v-if="!a.primary" class="text-[0.8rem] text-muted underline underline-offset-4 hover:text-forest" @click="setPrimary(a.id)">
            Jadikan utama
          </button>
        </div>
      </article>

      <button class="flex min-h-[12rem] flex-col items-center justify-center gap-3 border border-dashed border-line bg-surface/60 text-muted transition hover:border-olive/60 hover:text-forest">
        <Plus class="h-5 w-5 text-gold" :stroke-width="1.4" />
        <span class="text-[0.85rem]">Tambah alamat baru</span>
      </button>
    </div>
  </AccountShell>
</template>
