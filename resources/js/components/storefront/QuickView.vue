<script setup>
import { ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppRating from '@/components/ui/AppRating.vue'
import QuantityInput from '@/components/ui/QuantityInput.vue'
import { formatIDR } from '@/composables/useFormat'
import { useCart } from '@/composables/useCart'

const props = defineProps({ product: { type: Object, default: null } })
const emit = defineEmits(['close'])

const { add } = useCart()
const qty = ref(1)
watch(() => props.product, () => { qty.value = 1 })

const addToCart = () => {
  add(props.product, qty.value)
  emit('close')
}
</script>

<template>
  <AppModal :open="!!product" label="Lihat cepat produk" @close="emit('close')">
    <div v-if="product" class="grid sm:grid-cols-[minmax(0,1.08fr)_minmax(19rem,0.92fr)]">
      <div class="aspect-square border-b border-line bg-ivory/55 sm:aspect-auto sm:min-h-[32rem] sm:border-b-0 sm:border-r">
        <div v-if="product.image" class="flex h-full w-full items-center justify-center p-4 sm:p-7">
          <img
            :src="product.image"
            :alt="product.name"
            loading="lazy"
            class="h-full w-full object-contain object-center"
          />
        </div>
        <ProductArt v-else :art="product.art" :tone="product.id" />
      </div>
      <div class="flex flex-col p-5 pr-14 sm:p-8 sm:pr-10">
        <p class="text-[0.68rem] uppercase tracking-[0.16em] text-olive">{{ product.category }}</p>
        <h2 class="mt-2.5 text-[1.65rem] leading-tight sm:text-[1.9rem]">{{ product.name }}</h2>
        <div class="mt-3"><AppRating :value="product.rating" :count="product.reviews" /></div>
        <p v-if="product.short" class="mt-5 text-[0.88rem] leading-relaxed text-muted">{{ product.short }}</p>
        <div class="mt-6 flex flex-wrap items-baseline gap-x-3 gap-y-1">
          <p class="font-display text-[1.75rem] text-forest sm:text-3xl">{{ formatIDR(product.price) }}</p>
          <p v-if="product.comparePrice" class="text-[0.82rem] text-muted line-through">{{ formatIDR(product.comparePrice) }}</p>
        </div>
        <p class="mt-2 text-[0.75rem]" :class="product.stock === 0 ? 'text-danger' : 'text-success'">
          {{ product.stock === 0 ? 'Stok habis' : `Tersedia ${product.stock} ${product.unit || 'pcs'}` }}
        </p>

        <div class="mt-6 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
          <QuantityInput v-model="qty" :max="Math.max(product.stock, 1)" />
          <AppButton class="min-w-0 flex-1 whitespace-nowrap" :disabled="product.stock === 0" @click="addToCart">
            {{ product.stock === 0 ? 'Stok habis' : 'Tambah ke keranjang' }}
          </AppButton>
        </div>
        <Link
          :href="`/produk/${product.slug}`"
          class="link-underline mt-5 self-start text-[0.82rem] text-forest"
          @click="emit('close')"
        >
          Lihat detail lengkap
        </Link>
      </div>
    </div>
  </AppModal>
</template>
