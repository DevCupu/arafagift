<script setup>
import { Plus } from 'lucide-vue-next'
import DataTable from '@/components/admin/DataTable.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { promotions } from '@/data/admin'

const columns = [
  { key: 'code', label: 'Kode' },
  { key: 'type', label: 'Jenis' },
  { key: 'usage', label: 'Terpakai' },
  { key: 'period', label: 'Periode' },
  { key: 'status', label: 'Status' },
]
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Promo</h1>
        <p class="mt-3 text-[0.85rem] text-muted">Kode potongan yang bisa dipakai pembeli saat checkout.</p>
      </div>
      <AppButton size="sm">
        <template #icon><Plus class="h-3.5 w-3.5" /></template>
        Buat kode promo
      </AppButton>
    </header>

    <div class="mt-8">
      <DataTable :columns="columns" :rows="promotions" row-key="code">
        <template #cell-code="{ row }"><span class="font-medium tracking-wide">{{ row.code }}</span></template>
        <template #cell-usage="{ row }"><span class="text-muted">{{ row.usage }}</span></template>
        <template #cell-period="{ row }"><span class="text-muted">{{ row.period }}</span></template>
        <template #cell-status="{ row }">
          <StatusPill :label="row.status === 'active' ? 'Berjalan' : 'Berakhir'" :tone="row.status === 'active' ? 'success' : 'muted'" />
        </template>
      </DataTable>
    </div>
  </div>
</template>
