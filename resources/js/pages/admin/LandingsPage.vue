<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ExternalLink, Plus, Trash2 } from 'lucide-vue-next'
import DataTable from '@/components/admin/DataTable.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'

defineProps({ pages: { type: Array, required: true } })
const { push } = useToast()

const columns = [
  { key: 'title', label: 'Halaman' },
  { key: 'slug', label: 'Alamat' },
  { key: 'blockCount', label: 'Blok' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right' },
]

const destroy = (row) => {
  if (!confirm(`Hapus landing "${row.title}"? Halaman /${row.slug} akan langsung mati.`)) return
  router.delete(`/admin/landing/${row.slug}`, {
    preserveScroll: true,
    onSuccess: () => push('Landing dihapus', { tone: 'success' }),
  })
}
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Landing</h1>
        <p class="mt-3 text-[0.85rem] text-muted">
          Halaman khusus iklan Meta/Google Ads. Tiap halaman disusun dari blok yang bisa diurutkan bebas.
        </p>
      </div>
      <AppButton size="sm" to="/admin/landing/baru">
        <template #icon><Plus class="h-3.5 w-3.5" /></template>
        Landing baru
      </AppButton>
    </header>

    <div class="mt-8">
      <DataTable
        :columns="columns" :rows="pages" row-key="slug"
        :base-empty="pages.length === 0" empty-title="Belum ada landing page"
        empty-body="Buat halaman pertama untuk kampanye iklan, lalu arahkan traffic ke alamatnya."
      >
        <template #cell-title="{ row }">
          <Link :href="`/admin/landing/${row.slug}`" class="font-medium text-forest hover:underline">{{ row.title }}</Link>
        </template>
        <template #cell-slug="{ row }">
          <a :href="`/${row.slug}`" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-muted hover:text-forest">
            /{{ row.slug }}<ExternalLink class="h-3 w-3" />
          </a>
        </template>
        <template #cell-blockCount="{ row }"><span class="text-muted">{{ row.blockCount }}</span></template>
        <template #cell-status="{ row }">
          <StatusPill
            :label="row.status === 'publish' ? 'Tayang' : 'Draft'"
            :tone="row.status === 'publish' ? 'success' : 'muted'"
          />
        </template>
        <template #cell-actions="{ row }">
          <button
            type="button" class="grid h-8 w-8 place-items-center text-muted transition hover:text-danger"
            aria-label="Hapus" @click="destroy(row)"
          >
            <Trash2 class="h-3.5 w-3.5" />
          </button>
        </template>
      </DataTable>
    </div>
  </div>
</template>
