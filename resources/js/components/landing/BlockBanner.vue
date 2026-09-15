<script setup>
import { computed } from 'vue'
import { ArrowUpRight, MessageCircle } from 'lucide-vue-next'
import { useStore } from '@/composables/useStore'

const props = defineProps({ content: { type: Object, required: true } })
const { whatsappHref } = useStore()

const items = computed(() => (props.content.items ?? []).filter((b) => b.image || b.title))
const linkFor = (item) => item.href || whatsappHref('Halo ArafahGift, saya tertarik dengan promo ini.')
</script>

<template>
  <section v-if="items.length" class="bg-forest-deep px-0 pb-14 sm:pb-20" v-reveal>
    <div class="shell">
      <div class="grid gap-4 lg:grid-cols-[1.28fr_0.72fr]">
        <a
          v-for="(item, i) in items.slice(0, 2)"
          :key="`${item.title}-${i}`"
          :href="linkFor(item)"
          class="reveal-child group relative flex min-h-[310px] overflow-hidden rounded-2xl border border-ivory/12 bg-forest text-left shadow-lift transition duration-300 ease-calm hover:-translate-y-0.5 hover:border-gold/70 lg:min-h-[390px]"
          :class="i === 1 ? 'lg:min-h-[390px]' : ''"
          :style="{ '--reveal-delay': `${i * 120}ms` }"
        >
          <img
            v-if="item.image"
            :src="item.image"
            :alt="item.alt || item.title || 'Promo ArafahGift'"
            class="slow-zoom absolute inset-0 h-full w-full object-cover transition duration-[900ms] ease-calm group-hover:scale-[1.04]"
            loading="lazy"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-forest-deep via-forest-deep/42 to-forest-deep/8" />
          <div class="relative z-10 mt-auto w-full p-5 sm:p-7">
            <div class="mb-4 flex items-center justify-between gap-4">
              <span
                v-if="item.badge"
                class="rounded-full border border-gold/50 bg-gold px-3 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-forest-deep"
              >
                {{ item.badge }}
              </span>
              <span class="ml-auto grid h-10 w-10 place-items-center rounded-full border border-ivory/20 bg-ivory/10 text-ivory backdrop-blur transition group-hover:border-gold group-hover:text-gold">
                <ArrowUpRight class="h-4 w-4" :stroke-width="1.7" />
              </span>
            </div>
            <h3
              v-if="item.title"
              class="max-w-[24rem] text-[1.55rem] leading-tight text-ivory sm:text-[2rem]"
              :class="i === 0 ? 'lg:text-[2.45rem]' : ''"
            >
              {{ item.title }}
            </h3>
            <span
              v-if="item.ctaLabel"
              class="mt-5 inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-gold px-5 py-2.5 text-[0.82rem] font-semibold text-forest-deep transition group-hover:bg-gold-soft"
            >
              <MessageCircle class="h-4 w-4" :stroke-width="1.7" />
              {{ item.ctaLabel }}
            </span>
          </div>
        </a>
      </div>
    </div>
  </section>
</template>
