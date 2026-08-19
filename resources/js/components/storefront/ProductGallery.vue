<script setup>
import { ref, watch } from 'vue'
import ProductArt from '@/components/art/ProductArt.vue'

const props = defineProps({ product: { type: Object, required: true } })

// Sementara: 4 "angle" dibuat dari variasi tone motif.
// Ganti array ini dengan product.images saat foto asli tersedia.
const views = [0, 1, 2, 3]
const active = ref(0)
watch(() => props.product.slug, () => { active.value = 0 })
</script>

<template>
  <div class="flex flex-col-reverse gap-4 lg:flex-row">
    <div class="flex gap-3 lg:flex-col">
      <button
        v-for="(v, i) in views" :key="i"
        class="arch h-20 w-16 overflow-hidden border transition"
        :class="active === i ? 'border-forest' : 'border-line hover:border-olive/50'"
        :aria-label="`Tampilan ${i + 1}`" :aria-current="active === i"
        @click="active = i"
      >
        <img v-if="product.image" :src="product.image" :alt="product.name" class="h-full w-full object-cover" />
        <ProductArt v-else :art="product.art" :tone="v" />
      </button>
    </div>

    <div class="arch arch--deep relative flex-1 overflow-hidden border border-line bg-surface">
      <div class="aspect-[4/5] w-full">
        <Transition
          mode="out-in"
          enter-active-class="transition duration-300 ease-calm" enter-from-class="opacity-0 scale-[1.02]"
          leave-active-class="transition duration-200" leave-to-class="opacity-0"
        >
          <div :key="active" class="h-full w-full">
            <img v-if="product.image" :src="product.image" :alt="product.name" class="h-full w-full object-cover" />
            <ProductArt v-else :art="product.art" :tone="views[active]" />
          </div>
        </Transition>
      </div>
    </div>
  </div>
</template>
