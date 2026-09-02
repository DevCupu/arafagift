<script setup>
import { Trash2, X } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'

const props = defineProps({
  count: { type: Number, required: true },
  label: { type: String, default: 'item' },
  confirmMessage: { type: String, default: null },
  loading: { type: Boolean, default: false },
})
const emit = defineEmits(['clear', 'delete'])

const onDelete = () => {
  const message = props.confirmMessage ?? `Hapus ${props.count} ${props.label} terpilih? Tindakan ini tidak bisa dibatalkan.`
  if (!confirm(message)) return
  emit('delete')
}
</script>

<template>
  <div v-if="count > 0" class="flex flex-wrap items-center justify-between gap-3 border border-olive/40 bg-ivory px-5 py-3">
    <p class="text-[0.83rem] text-forest"><strong class="tabular-nums">{{ count }}</strong> {{ label }} dipilih</p>
    <div class="flex items-center gap-2">
      <AppButton size="sm" variant="quiet" type="button" @click="emit('clear')">
        <template #icon><X class="h-3.5 w-3.5" /></template>
        Batal
      </AppButton>
      <AppButton size="sm" variant="outline" type="button" :loading="loading" @click="onDelete">
        <template #icon><Trash2 class="h-3.5 w-3.5" /></template>
        Hapus {{ count }}
      </AppButton>
    </div>
  </div>
</template>
