<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { router } from '@inertiajs/vue3'
import AppRating from '@/components/ui/AppRating.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { formatDate } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  reviews: { type: Array, required: true },
  counts: { type: Object, default: () => ({}) },
  status: { type: String, default: null },
})
const { push } = useToast()

const filters = [
  { id: null, label: 'Semua' },
  { id: 'pending', label: 'Menunggu' },
  { id: 'approved', label: 'Disetujui' },
  { id: 'rejected', label: 'Ditolak' },
]
const total = (id) => (id ? props.counts[id] ?? 0 : Object.values(props.counts).reduce((a, b) => a + b, 0))
const filter = (id) => router.get('/admin/ulasan', id ? { status: id } : {}, { preserveState: true, replace: true })

const setStatus = (review, status) => {
  router.put(`/admin/ulasan/${review.id}`, { status, admin_note: review.adminNote ?? '' }, {
    preserveScroll: true,
    onSuccess: () => push('Ulasan diperbarui', { tone: 'success' }),
    onError: () => push('Gagal memperbarui ulasan', { tone: 'danger' }),
  })
}

const destroy = (review) => {
  if (!window.confirm('Hapus ulasan ini?')) return
  router.delete(`/admin/ulasan/${review.id}`, {
    preserveScroll: true,
    onSuccess: () => push('Ulasan dihapus', { tone: 'success' }),
  })
}

const statusLabel = { pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' }
</script>

<template>
  <div>
    <header>
      <h1 class="text-[2.1rem] leading-none">Ulasan</h1>
      <p class="mt-3 text-[0.85rem] text-muted">Ulasan tayang di halaman produk setelah disetujui.</p>
    </header>

    <div class="mt-8 flex flex-wrap gap-2">
      <button
        v-for="f in filters" :key="f.label" type="button"
        class="rounded-full border px-4 py-1.5 text-[0.78rem] transition"
        :class="status === f.id ? 'border-forest bg-forest text-ivory' : 'border-line bg-surface text-forest hover:border-olive/50'"
        @click="filter(f.id)"
      >{{ f.label }} ({{ total(f.id) }})</button>
    </div>

    <p v-if="!reviews.length" class="mt-10 border border-dashed border-line px-6 py-12 text-center text-[0.85rem] text-muted">
      Belum ada ulasan pada filter ini.
    </p>

    <ul v-else class="mt-6 space-y-4">
      <li v-for="r in reviews" :key="r.id" class="border border-line bg-surface p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <AppRating :value="r.rating" />
            <p v-if="r.title" class="mt-2 font-display text-lg text-forest">{{ r.title }}</p>
          </div>
          <span class="rounded-full border border-line bg-ivory px-3 py-1 text-[0.72rem] font-semibold text-forest">{{ statusLabel[r.status] }}</span>
        </div>
        <p class="mt-2 whitespace-pre-line text-[0.88rem] leading-relaxed text-muted">{{ r.body }}</p>
        <p class="mt-3 text-[0.72rem] text-muted">
          {{ r.author }}<template v-if="r.email"> · {{ r.email }}</template>
          · <a v-if="r.product" :href="`/produk/${r.product.slug}`" target="_blank" rel="noopener" class="link-underline">{{ r.product.name }}</a>
          <template v-if="r.orderNumber"> · {{ r.orderNumber }}</template>
          · {{ formatDate(r.createdAt) }}
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
          <AppButton v-if="r.status !== 'approved'" size="sm" @click="setStatus(r, 'approved')">Setujui</AppButton>
          <AppButton v-if="r.status !== 'rejected'" size="sm" variant="quiet" @click="setStatus(r, 'rejected')">Tolak</AppButton>
          <AppButton size="sm" variant="ghost" @click="destroy(r)">Hapus</AppButton>
        </div>
      </li>
    </ul>
  </div>
</template>
