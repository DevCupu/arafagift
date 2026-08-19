<script setup>
import { Minus, Plus } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: Number, default: 1 },
  min: { type: Number, default: 1 },
  max: { type: Number, default: 99 },
  size: { type: String, default: 'md' },
})
const emit = defineEmits(['update:modelValue'])

const set = (v) => emit('update:modelValue', Math.min(props.max, Math.max(props.min, v)))
</script>

<template>
  <div
    :class="[
      'inline-flex items-center border border-line bg-surface',
      size === 'sm' ? 'h-8' : 'h-11',
    ]"
  >
    <button
      type="button"
      class="grid h-full w-9 place-items-center text-forest transition hover:bg-ivory disabled:opacity-30"
      :disabled="modelValue <= min"
      aria-label="Kurangi jumlah"
      @click="set(modelValue - 1)"
    >
      <Minus class="h-3.5 w-3.5" />
    </button>
    <span
      class="w-9 text-center text-[0.85rem] font-medium tabular-nums text-forest"
      aria-live="polite"
    >{{ modelValue }}</span>
    <button
      type="button"
      class="grid h-full w-9 place-items-center text-forest transition hover:bg-ivory disabled:opacity-30"
      :disabled="modelValue >= max"
      aria-label="Tambah jumlah"
      @click="set(modelValue + 1)"
    >
      <Plus class="h-3.5 w-3.5" />
    </button>
  </div>
</template>
