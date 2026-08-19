<script setup>
import { computed } from 'vue'
import { statusMeta } from '@/data/admin'

const props = defineProps({ status: String, label: String, tone: String })
const meta = computed(() => (props.status ? statusMeta(props.status) : { label: props.label, tone: props.tone }))

const tones = {
  success: 'border-success/30 bg-success/10 text-success',
  info: 'border-olive/30 bg-olive/12 text-olive',
  warn: 'border-gold/45 bg-gold/12 text-[#7A5F26]',
  danger: 'border-danger/30 bg-danger/10 text-danger',
  muted: 'border-line bg-ivory text-muted',
}
</script>

<template>
  <span :class="['inline-flex items-center gap-1.5 border px-2.5 py-1 text-[0.7rem] font-medium', tones[meta.tone] || tones.muted]">
    <span class="h-1 w-1 rotate-45 bg-current" />{{ meta.label }}
  </span>
</template>
