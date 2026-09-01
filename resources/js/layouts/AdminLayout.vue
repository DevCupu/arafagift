<script setup>
import { computed, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Menu } from 'lucide-vue-next'
import AdminSidebar from '@/components/admin/AdminSidebar.vue'
import ToastHost from '@/components/ui/ToastHost.vue'

const route = usePage()
const open = ref(false)
watch(() => route.url, () => (open.value = false))

const SEG_LABELS = {
  dashboard: 'Dashboard',
  pesanan: 'Pesanan',
  produk: 'Produk',
  kategori: 'Kategori',
  pelanggan: 'Pelanggan',
  inventori: 'Inventori',
  supplier: 'Supplier',
  promo: 'Promo',
  konten: 'Konten',
  laporan: 'Laporan',
  pengaturan: 'Pengaturan',
}

const title = computed(() => {
  const seg = route.url.split('?')[0].split('/')[2]
  return SEG_LABELS[seg] ?? 'Admin'
})
</script>

<template>
  <div class="min-h-screen bg-ivory">
    <AdminSidebar :open="open" @close="open = false" />
    <div v-if="open" class="fixed inset-0 z-[65] bg-forest-deep/40 lg:hidden" @click="open = false" />

    <div class="lg:pl-64">
      <header class="sticky top-0 z-40 flex h-16 items-center gap-4 border-b border-line bg-ivory/92 px-5 backdrop-blur-md sm:px-8">
        <button class="-ml-2 grid h-10 w-10 place-items-center text-forest lg:hidden" aria-label="Buka menu admin" @click="open = true">
          <Menu class="h-5 w-5" :stroke-width="1.5" />
        </button>

        <div class="flex min-w-0 items-center gap-3">
          <span class="hidden h-4 w-px bg-line sm:block" />
          <p class="truncate text-[0.9rem] font-medium text-forest">{{ title }}</p>
        </div>

        <div class="ml-auto flex items-center gap-3">
          <Link href="/" class="hidden text-[0.78rem] text-muted transition hover:text-forest sm:block">Lihat storefront →</Link>
          <div class="flex items-center gap-3 border-l border-line pl-3">
            <span class="grid h-9 w-9 place-items-center rounded-full bg-forest text-[0.72rem] font-semibold text-ivory">AG</span>
            <div class="hidden sm:block">
              <p class="text-[0.8rem] leading-none text-forest">Admin</p>
              <p class="mt-1 text-[0.7rem] leading-none text-muted">Pemilik toko</p>
            </div>
          </div>
        </div>
      </header>

      <main class="px-5 py-8 sm:px-8 sm:py-10"><slot /></main>
    </div>
    <ToastHost />
  </div>
</template>