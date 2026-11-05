<script setup>
import { onMounted, ref } from 'vue'
import { getConsent, hasAnalytics, initAnalytics, loadAnalytics, setConsent } from '@/composables/useAnalytics'

const visible = ref(false)

onMounted(() => {
  if (!hasAnalytics()) return

  const choice = getConsent()
  if (choice === 'granted') initAnalytics()
  else if (choice !== 'denied') visible.value = true
})

const accept = () => {
  setConsent('granted')
  visible.value = false
  loadAnalytics()
}

const deny = () => {
  setConsent('denied')
  visible.value = false
}
</script>

<template>
  <div
    v-if="visible"
    role="region"
    aria-label="Persetujuan cookie analitik"
    class="fixed inset-x-0 bottom-0 z-[70] border-t border-gold/35 bg-forest px-4 py-4 text-ivory shadow-[0_-12px_40px_rgba(8,32,22,0.28)] sm:px-6"
  >
    <div class="mx-auto flex max-w-5xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
      <p class="text-[0.8rem] leading-relaxed text-ivory/85">
        Kami memakai cookie analitik (Google Analytics &amp; Meta Pixel) untuk memahami cara pengunjung memakai toko ini.
        Tidak ada data pembayaran yang dibagikan.
        <a
          href="/legal/kebijakan-privasi"
          class="font-medium text-gold underline underline-offset-4 transition hover:text-gold/80"
        >Pelajari</a>
      </p>
      <div class="flex flex-none items-center gap-2">
        <button
          type="button"
          class="rounded-lg border border-ivory/30 px-4 py-2 text-[0.75rem] font-semibold text-ivory transition hover:bg-ivory/10"
          @click="deny"
        >Tolak</button>
        <button
          type="button"
          class="rounded-lg border border-gold bg-gold px-4 py-2 text-[0.75rem] font-semibold text-forest-deep transition hover:bg-gold/90"
          @click="accept"
        >Terima</button>
      </div>
    </div>
  </div>
</template>
