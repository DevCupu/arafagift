<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { AlertCircle, Calendar, Info } from 'lucide-vue-next'

const props = defineProps({ message: String })
const page = usePage()

const store = computed(() => page.props.store ?? {})
const storeStatus = computed(() => store.value.storeStatus ?? 'open')
const isHoliday = computed(() => storeStatus.value === 'holiday')
const isMaintenance = computed(() => storeStatus.value === 'maintenance')
</script>

<template>
  <!-- Holiday Announcement Banner -->
  <div v-if="isHoliday" class="border-b border-amber-500/30 bg-amber-600 text-white shadow-sm">
    <div class="shell flex flex-wrap items-center justify-between gap-2 py-2 text-xs">
      <div class="flex items-center gap-2">
        <Info class="h-4 w-4 flex-none text-amber-200" />
        <span class="font-semibold">{{ store.maintenanceTitle || 'Pengumuman Toko Libur' }}:</span>
        <span>{{ store.closedMessage || 'Toko sedang dalam masa libur operasional.' }}</span>
      </div>
      <div v-if="store.maintenanceEndTime" class="flex items-center gap-1.5 text-[0.72rem] text-amber-100 font-medium">
        <Calendar class="h-3.5 w-3.5" />
        <span>Buka kembali: {{ store.maintenanceEndTime }}</span>
      </div>
    </div>
  </div>

  <!-- Maintenance Mode Banner -->
  <div v-else-if="isMaintenance" class="border-b border-rose-600/30 bg-rose-700 text-white shadow-sm">
    <div class="shell flex flex-wrap items-center justify-between gap-2 py-2 text-xs">
      <div class="flex items-center gap-2">
        <AlertCircle class="h-4 w-4 flex-none text-rose-200" />
        <span class="font-semibold">{{ store.maintenanceTitle || 'Pemeliharaan Sistem' }}:</span>
        <span>{{ store.closedMessage || 'Toko sedang dalam pemeliharaan sementara.' }}</span>
      </div>
      <div v-if="store.maintenanceEndTime" class="flex items-center gap-1.5 text-[0.72rem] text-rose-100 font-medium">
        <Calendar class="h-3.5 w-3.5" />
        <span>Selesai: {{ store.maintenanceEndTime }}</span>
      </div>
    </div>
  </div>

  <!-- Standard Marquee / Announcement (when open) -->
  <div v-else-if="message" class="border-b border-forest-soft/30 bg-forest">
    <div class="shell flex h-9 items-center justify-center gap-3 text-[0.62rem] tracking-[0.1em] sm:text-[0.7rem] sm:tracking-[0.12em]">
      <span class="hidden h-1 w-1 rotate-45 bg-ivory/70 sm:block" />
      <p class="truncate uppercase text-ivory">{{ message }}</p>
      <span class="hidden h-1 w-1 rotate-45 bg-ivory/70 sm:block" />
    </div>
  </div>
</template>
