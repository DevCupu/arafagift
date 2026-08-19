<script setup>
import { BadgeCheck, Gift, Send, Sparkles } from 'lucide-vue-next'
defineProps({
  items: { type: Array, default: () => [] },
  dark: { type: Boolean, default: false },
})
const icons = { Sparkles, Gift, BadgeCheck, Send }
</script>

<template>
  <ul :class="[
    'grid gap-px overflow-hidden sm:grid-cols-2 lg:grid-cols-4',
    dark ? 'border border-forest-soft/30 bg-forest-soft/20' : 'border border-line bg-line'
  ]">
    <li v-for="item in items" :key="item.title"
      :class="['px-6 py-8', dark ? 'bg-forest' : 'bg-surface']"
    >
      <component :is="icons[item.icon]" class="h-5 w-5 text-gold" :stroke-width="1.4" />
      <h3 :class="['mt-5 font-display text-[1.25rem] leading-none', dark ? 'text-ivory' : '']">{{ item.title }}</h3>
      <p :class="['mt-3 text-[0.83rem] leading-relaxed', dark ? 'text-ivory/60' : 'text-muted']">{{ item.body }}</p>
    </li>
  </ul>
</template>
