<script setup>
defineProps({
  eyebrow: String,
  title: String,
  sub: String,
  align: { type: String, default: 'left' }, // left | center
  dark: { type: Boolean, default: false },
})
</script>

<template>
  <div :class="['max-w-2xl', align === 'center' ? 'mx-auto text-center' : '']">
    <p v-if="eyebrow" :class="['eyebrow', align === 'center' ? 'eyebrow--center justify-center' : '', dark ? 'text-gold' : '']">
      {{ eyebrow }}
    </p>
    <h2 :class="['mt-5 text-[2rem] leading-[1.06] sm:text-[2.6rem]', dark ? 'text-ivory' : '']">
      <slot name="title">{{ title }}</slot>
    </h2>
    <p v-if="sub || $slots.sub" :class="['mt-4 text-[0.95rem] leading-relaxed', dark ? 'text-ivory/60' : 'text-muted']">
      <slot name="sub">{{ sub }}</slot>
    </p>
  </div>
</template>
