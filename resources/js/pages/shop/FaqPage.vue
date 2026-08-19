<script setup>
import FaqAccordion from '@/components/storefront/FaqAccordion.vue'
import AppButton from '@/components/ui/AppButton.vue'

defineProps({ faqs: { type: Array, required: true } })
</script>

<template>
  <div class="shell grid gap-12 py-14 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20 lg:py-20">
    <div class="lg:sticky lg:top-28 lg:h-fit">
      <p class="eyebrow">FAQ</p>
      <h1 class="mt-6 text-[2.4rem] leading-[1.05] sm:text-[3rem]">Pertanyaan yang sering masuk</h1>
      <p class="mt-5 max-w-sm text-[0.93rem] leading-relaxed text-muted">
        Belum terjawab? Kirim pesan ke WhatsApp kami — dibalas pada jam kerja, biasanya di bawah 30 menit.
      </p>
      <AppButton href="https://wa.me/6281234567890" variant="outline" class="mt-7">Tanya via WhatsApp</AppButton>
    </div>
    <FaqAccordion :items="faqs" />
  </div>
</template>
