<script setup>
import { computed, ref } from 'vue'
import { ArrowRight } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import { useStore } from '@/composables/useStore'
import { useHeroMotion } from '@/composables/useHeroMotion'

const props = defineProps({ content: { type: Object, required: true } })
const heroRoot = ref(null)
useHeroMotion(heroRoot)

const { whatsappHref } = useStore()

// Lebih pendek dari hero homepage: landing iklan harus muat 1 layar di mobile.
const lines = computed(() => (props.content.headline ?? '').split('\n'))
const href = computed(() => props.content.cta?.href || whatsappHref(`Halo Arafagift, saya tertarik dengan penawaran ini.`))
</script>

<template>
  <section ref="heroRoot" class="relative overflow-hidden bg-forest-deep">
    <div class="grain pointer-events-none absolute inset-0 opacity-25" />

    <div v-if="content.image" class="absolute inset-y-0 right-0 w-full lg:w-[52%]" aria-hidden="true">
      <div class="hero-motion-media absolute inset-x-0 -top-[8%] -bottom-[8%]">
        <img :src="content.image" alt="" class="hero-motion-image h-full w-full object-cover object-center" loading="eager" fetchpriority="high" />
      </div>
      <div class="absolute inset-y-0 left-0 w-[55%] bg-gradient-to-r from-forest-deep via-forest-deep/80 to-transparent" />
      <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-forest-deep/70 to-transparent" />
    </div>

    <div class="shell relative flex flex-col justify-center py-16 sm:py-20">
      <div class="w-full max-w-[540px]">
        <div v-if="content.eyebrow" class="hero-motion-eyebrow flex items-center gap-3">
          <span class="h-px w-8 bg-gold" />
          <p class="text-[0.68rem] font-semibold uppercase tracking-[0.25em] text-gold">{{ content.eyebrow }}</p>
        </div>

        <h1 class="mt-5" style="font-size: clamp(2.25rem, 5.8vw, 4rem); line-height: 1.04; font-weight: 700;">
          <template v-for="(line, i) in lines" :key="i">
            <span
              class="block overflow-hidden text-balance"
              :class="i === lines.length - 1 ? 'text-gold' : 'text-ivory'"
            ><span class="hero-motion-line block">{{ line }}</span></span>
            <span v-if="i < lines.length - 1" class="sr-only"> </span>
          </template>
        </h1>
        <span aria-hidden="true" class="hero-motion-rule mt-6 block h-px w-20 origin-left bg-gold" />

        <p v-if="content.sub" class="hero-motion-detail mt-5 max-w-[30rem] text-[0.98rem] leading-relaxed text-ivory/72 sm:text-[1.05rem]">
          {{ content.sub }}
        </p>

        <div v-if="content.cta?.label" class="hero-motion-detail mt-8">
          <AppButton :href="href" variant="gold" size="lg">
            {{ content.cta.label }}
            <template #icon><ArrowRight class="h-4 w-4" /></template>
          </AppButton>
        </div>
      </div>
    </div>
  </section>
</template>
