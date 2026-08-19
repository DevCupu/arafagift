<script setup>
import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Bell, Menu, Search } from 'lucide-vue-next'
import AdminSidebar from '@/components/admin/AdminSidebar.vue'

const page = usePage()
const open = ref(false)
watch(() => page.url, () => (open.value = false))
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

        <div class="relative hidden max-w-sm flex-1 sm:block">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" :stroke-width="1.5" />
          <label class="sr-only" for="admin-search">Cari</label>
          <input id="admin-search" class="field pl-9" placeholder="Cari pesanan, produk, pelanggan…" />
        </div>

        <div class="ml-auto flex items-center gap-3">
          <button class="relative grid h-10 w-10 place-items-center text-forest" aria-label="Notifikasi">
            <Bell class="h-[18px] w-[18px]" :stroke-width="1.5" />
            <span class="absolute right-2.5 top-2.5 h-1.5 w-1.5 rounded-full bg-gold" />
          </button>
          <div class="flex items-center gap-3 border-l border-line pl-3">
            <span class="grid h-9 w-9 place-items-center rounded-full bg-forest text-[0.72rem] font-semibold text-ivory">AG</span>
            <div class="hidden sm:block">
              <p class="text-[0.8rem] leading-none text-forest">Admin Arafah</p>
              <p class="mt-1 text-[0.7rem] leading-none text-muted">Pemilik toko</p>
            </div>
          </div>
        </div>
      </header>

      <main class="px-5 py-8 sm:px-8 sm:py-10"><slot /></main>
    </div>
  </div>
</template>
