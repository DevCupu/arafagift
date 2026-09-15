<script setup>
import { computed } from 'vue'
import { BadgeCheck, Check, Clock, Gift, Heart, Send, Shield, Sparkles, Star, Truck, Users, Wallet } from 'lucide-vue-next'

const props = defineProps({ content: { type: Object, required: true } })

const items = computed(() => props.content.items ?? [])
const icons = { Sparkles, Gift, BadgeCheck, Send, Truck, Shield, Users, Heart, Check, Star, Clock, Wallet }
</script>

<template>
  <section v-if="items.length" class="bg-forest-deep py-16 text-ivory sm:py-20">
    <div class="shell">
      <div class="max-w-2xl">
        <h2 v-if="content.title" class="!text-ivory text-[1.9rem] sm:text-[2.55rem]">{{ content.title }}</h2>
        <p class="mt-4 max-w-[56ch] text-[0.95rem] leading-7 !text-ivory/82">
          Alur pemesanan dibuat jelas dari kurasi isi, packing, sampai pengiriman, supaya tim travel tidak perlu mengurus detail kecil satu per satu.
        </p>
      </div>

      <div class="mt-9 grid gap-4 md:grid-cols-2 lg:grid-cols-[1.1fr_0.9fr_0.9fr]" v-reveal>
        <article
          v-for="(item, i) in items"
          :key="item.title"
          class="reveal-child group relative overflow-hidden rounded-2xl border border-ivory/14 bg-forest p-5 shadow-soft transition duration-300 ease-calm hover:border-gold/50 hover:bg-forest-soft/30 sm:p-6"
          :class="i === 0 ? 'md:col-span-2 lg:col-span-1 lg:row-span-2' : ''"
          :style="{ '--reveal-delay': `${i * 85}ms` }"
        >
          <div v-if="item.image" class="-mx-5 -mt-5 mb-5 aspect-[16/10] overflow-hidden sm:-mx-6 sm:-mt-6">
            <img :src="item.image" :alt="item.title" class="h-full w-full object-cover transition duration-[900ms] ease-calm group-hover:scale-[1.04]" loading="lazy" />
          </div>
          <div
            v-else
            class="grid h-12 w-12 place-items-center rounded-full border border-gold/30 bg-gold/12 text-gold"
            :class="i === 0 ? 'sm:h-14 sm:w-14' : ''"
          >
            <component :is="icons[item.icon] || Sparkles" class="h-5 w-5" :stroke-width="1.55" />
          </div>

          <div class="mt-7" :class="i === 0 ? 'sm:mt-10' : ''">
            <h3 class="!text-ivory text-[1.25rem] leading-tight" :class="i === 0 ? 'sm:text-[1.75rem]' : ''">
              {{ item.title }}
            </h3>
            <p class="mt-3 text-[0.88rem] leading-7 !text-ivory/78" :class="i === 0 ? 'max-w-[32rem] sm:text-[0.95rem]' : ''">
              {{ item.body }}
            </p>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
