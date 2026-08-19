<script setup>
import { TrendingDown, TrendingUp } from 'lucide-vue-next'
defineProps({ stat: { type: Object, required: true } })
</script>

<template>
  <div class="bg-surface px-6 py-6">
    <p class="text-[0.72rem] uppercase tracking-[0.14em] text-muted">{{ stat.label }}</p>
    <p class="mt-4 font-display text-[2.1rem] leading-none text-forest">{{ stat.value }}</p>
    <div class="mt-3 flex items-center gap-2 text-[0.75rem]">
      <span class="inline-flex items-center gap-1" :class="stat.trend === 'up' ? 'text-success' : 'text-danger'">
        <component :is="stat.trend === 'up' ? TrendingUp : TrendingDown" class="h-3.5 w-3.5" />{{ stat.delta }}
      </span>
      <span class="text-muted">{{ stat.note }}</span>
    </div>
  </div>
</template>
