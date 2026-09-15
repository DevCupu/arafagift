<script setup>
import { computed } from 'vue'
import { ArrowRight } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import { useStore } from '@/composables/useStore'

const props = defineProps({ content: { type: Object, required: true } })

const { whatsappHref } = useStore()
const href = computed(() => props.content.cta?.href || whatsappHref('Halo ArafahGift, saya mau tanya penawaran ini.'))
</script>

<template>
  <section class="py-16 sm:py-20">
    <div class="shell">
      <div class="relative overflow-hidden bg-forest px-6 py-14 text-center sm:px-12 sm:py-16" v-reveal>
        <div class="grain pointer-events-none absolute inset-0 opacity-20" />
        <div class="relative mx-auto max-w-[36rem]">
          <h2 class="text-[1.8rem] leading-tight text-ivory sm:text-[2.3rem]">{{ content.headline }}</h2>
          <p v-if="content.sub" class="mt-4 text-[0.92rem] leading-relaxed text-ivory/65">{{ content.sub }}</p>
          <div v-if="content.cta?.label" class="mt-8 flex justify-center">
            <AppButton :href="href" variant="gold" size="lg">
              {{ content.cta.label }}
              <template #icon><ArrowRight class="h-4 w-4" /></template>
            </AppButton>
          </div>
          <p v-if="content.note" class="mt-5 text-[0.75rem] text-ivory/50">{{ content.note }}</p>
        </div>
      </div>
    </div>
  </section>
</template>
