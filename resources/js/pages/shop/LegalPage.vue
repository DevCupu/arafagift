<script setup>
defineProps({
  title: { type: String, required: true },
  updated: { type: String, required: true },
  sections: { type: Array, required: true },
})
</script>

<template>
  <div class="shell max-w-3xl py-14 lg:py-20">
    <p class="eyebrow">Informasi</p>
    <h1 class="mt-6 text-[2.4rem] leading-[1.05] sm:text-[3rem]">{{ title }}</h1>
    <p class="mt-4 text-[0.8rem] text-muted">Terakhir diperbarui {{ updated }}</p>

    <div class="mt-10 space-y-8">
      <section v-for="s in sections" :key="s.heading">
        <h2 class="font-display text-2xl text-forest">{{ s.heading }}</h2>
        <p class="mt-3 whitespace-pre-line text-[0.9rem] leading-relaxed text-muted">{{ s.body }}</p>
      </section>
    </div>
  </div>
</template>
