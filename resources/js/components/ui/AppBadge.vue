<script setup>
defineProps({
  tone: { type: String, default: 'gold' }, // gold | forest | olive | danger | muted
})
const tones = {
  gold: 'border-gold bg-gold text-forest-deep',
  forest: 'border-forest bg-forest text-ivory',
  olive: 'border-olive-light bg-olive text-ivory',
  danger: 'border-danger bg-danger text-ivory',
  muted: 'border-line bg-surface text-muted',
}
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1 rounded border px-1.5 py-0.5 text-[0.58rem] font-semibold uppercase tracking-[0.1em] sm:px-2 sm:py-1 sm:text-[0.65rem] sm:tracking-[0.14em]',
      tones[tone],
    ]"
  >
    <slot />
  </span>
</template>
