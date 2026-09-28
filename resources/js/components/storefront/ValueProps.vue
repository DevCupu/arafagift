<script setup>
import { computed } from 'vue'
import { BadgeCheck, Check, Clock, Gift, Heart, Send, Shield, Sparkles, Star, Truck, Users, Wallet } from 'lucide-vue-next'
const props = defineProps({
  items: { type: Array, default: () => [] },
  dark: { type: Boolean, default: false },
  // Homepage tetap 4 kolom; landing page mengirim 5 supaya 5 hooks tidak
  // menyisakan satu item yatim di baris kedua.
  cols: { type: Number, default: 4 },
  // Mode editorial bernomor (01/02/…): nomor di kiri, ikon di kanan.
  numbered: { type: Boolean, default: false },
})
const colClass = computed(() => (props.cols === 5 ? 'lg:grid-cols-5' : 'lg:grid-cols-4'))
const icons = { Sparkles, Gift, BadgeCheck, Send, Truck, Shield, Users, Heart, Check, Star, Clock, Wallet }
</script>

<template>
  <ul :class="[
    'grid gap-px overflow-hidden sm:grid-cols-2', colClass,
    dark ? 'border border-forest-soft/30 bg-forest-soft/20' : 'border border-line bg-line'
  ]">
    <li v-for="(item, i) in items" :key="item.title"
      :class="['group relative overflow-hidden px-6 py-9 sm:px-8', dark ? 'bg-forest' : 'bg-surface']"
    >
      <!-- Foto banner opsional: menggantikan ikon kalau diisi admin -->
      <div v-if="item.image" class="-mx-6 -mt-9 mb-5 aspect-[16/10] overflow-hidden">
        <img :src="item.image" :alt="item.title" class="h-full w-full object-cover" loading="lazy" />
      </div>
      <div v-else-if="numbered" class="flex flex-col items-center gap-3.5">
        <span class="font-display text-[0.7rem] font-semibold tracking-[0.22em] text-gold/75">
          {{ String(i + 1).padStart(2, '0') }}
        </span>
        <component
          :is="icons[item.icon]"
          class="h-5 w-5 text-gold transition-transform duration-300 ease-calm group-hover:-translate-y-0.5"
          :stroke-width="1.4"
        />
      </div>
      <component v-else :is="icons[item.icon]" class="h-5 w-5 text-gold" :stroke-width="1.4" />
      <h3 :class="['mt-6 font-display text-[1.25rem] leading-none', numbered ? 'text-center' : '', dark ? 'text-ivory' : '']">{{ item.title }}</h3>
      <p :class="['mt-3 text-[0.83rem] leading-relaxed', numbered ? 'text-center' : '', dark ? 'text-ivory/60' : 'text-muted']">{{ item.body }}</p>
    </li>
  </ul>
</template>
