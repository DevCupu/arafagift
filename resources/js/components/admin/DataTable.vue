<script setup>
defineProps({
  columns: { type: Array, required: true }, // [{ key, label, align, width }]
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
})
</script>

<template>
  <div class="overflow-x-auto border border-line bg-surface">
    <table class="w-full min-w-[46rem] border-collapse text-left">
      <thead>
        <tr class="border-b border-line">
          <th
            v-for="c in columns" :key="c.key"
            class="whitespace-nowrap px-5 py-3.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-muted"
            :class="c.align === 'right' ? 'text-right' : ''"
            :style="c.width ? { width: c.width } : null"
          >{{ c.label }}</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-line">
        <tr v-for="row in rows" :key="row[rowKey]" class="group transition hover:bg-ivory/70">
          <td
            v-for="c in columns" :key="c.key"
            class="px-5 py-4 align-middle text-[0.85rem] text-forest"
            :class="c.align === 'right' ? 'text-right' : ''"
          >
            <slot :name="`cell-${c.key}`" :row="row">{{ row[c.key] }}</slot>
          </td>
        </tr>
      </tbody>
    </table>
    <div v-if="!rows.length" class="px-5 py-16 text-center">
      <p class="font-display text-xl text-forest">Tidak ada data pada filter ini</p>
      <p class="mt-2 text-[0.83rem] text-muted">Ubah kata kunci atau atur ulang filter untuk melihat baris lainnya.</p>
    </div>
  </div>
</template>
