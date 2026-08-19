<script setup>
import { computed } from 'vue'
import logoForest from '@/assets/Logo ARAFA GIFT-1.png'
import logoWhite from '@/assets/logo putih arafa.png'

const props = defineProps({
  tone: { type: String, default: 'forest' }, // forest | ivory
  size: { type: String, default: 'md' }, // sm | md | lg
})

const logoSrc = computed(() => {
  return props.tone === 'ivory' ? logoWhite : logoForest
})

const heightClass = computed(() => {
  return props.size === 'lg' ? 'h-11 sm:h-14' : props.size === 'sm' ? 'h-7 sm:h-8' : 'h-8 sm:h-10'
})
</script>

<template>
  <img
    :src="logoSrc"
    alt="ArafahGift.id"
    :class="['w-auto object-contain transition-all duration-350', heightClass]"
  />
</template>

