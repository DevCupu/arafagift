<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import FaqAccordion from '@/components/storefront/FaqAccordion.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useStore } from '@/composables/useStore'

const props = defineProps({ faqs: { type: Array, required: true } })
const { whatsappHref } = useStore()

const faqJsonLd = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'FAQPage',
  mainEntity: props.faqs.map((faq) => ({
    '@type': 'Question',
    name: faq.q,
    acceptedAnswer: { '@type': 'Answer', text: faq.a },
  })),
}))
</script>

<template>
  <div class="shell grid gap-12 py-14 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20 lg:py-20">
    <Head title="FAQ Oleh-Oleh Haji &amp; Umrah">
      <meta name="description" content="Jawaban seputar pemesanan, pengiriman, dan pembayaran oleh-oleh &amp; hadiah haji umrah di ArafahGift.id." />
      <link rel="canonical" href="/faq" />
      <meta property="og:title" content="FAQ — ArafahGift.id" />
      <meta property="og:description" content="Jawaban seputar pemesanan, pengiriman, dan pembayaran oleh-oleh &amp; hadiah haji umrah." />
      <component :is="'script'" type="application/ld+json">{{ JSON.stringify(faqJsonLd) }}</component>
    </Head>
    <div class="lg:sticky lg:top-28 lg:h-fit">
      <p class="eyebrow">FAQ</p>
      <h1 class="mt-6 text-[2.4rem] leading-[1.05] sm:text-[3rem]">Pertanyaan yang sering masuk</h1>
      <p class="mt-5 max-w-sm text-[0.93rem] leading-relaxed text-muted">
        Belum terjawab? Kirim pesan ke WhatsApp kami — dibalas pada jam kerja, biasanya di bawah 30 menit.
      </p>
      <AppButton :href="whatsappHref('Halo ArafahGift, saya mau tanya:')" variant="outline" class="mt-7" target="_blank" rel="noopener">Tanya via WhatsApp</AppButton>
    </div>
    <FaqAccordion :items="faqs" />
  </div>
</template>
