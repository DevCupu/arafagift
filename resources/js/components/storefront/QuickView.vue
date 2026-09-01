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
    <div v-if="product" class="grid sm:grid-cols-2">
      <div class="aspect-[4/5] border-b border-line bg-ivory sm:border-b-0 sm:border-r">
        <img v-if="product.image" :src="product.image" :alt="product.name" loading="lazy" class="h-full w-full object-cover" />
        <ProductArt v-else :art="product.art" :tone="product.id" />
      </div>
      <div class="flex flex-col p-7 sm:p-9">
        <p class="text-[0.68rem] uppercase tracking-[0.16em] text-olive">{{ product.category }}</p>
        <h2 class="mt-3 text-[1.9rem] leading-tight">{{ product.name }}</h2>
        <div class="mt-3"><AppRating :value="product.rating" :count="product.reviews" /></div>
        <p class="mt-5 text-[0.88rem] leading-relaxed text-muted">{{ product.short }}</p>
        <p class="mt-6 font-display text-3xl text-forest">{{ formatIDR(product.price) }}</p>

        <div class="mt-6 flex items-center gap-3">
          <QuantityInput v-model="qty" :max="Math.max(product.stock, 1)" />
          <AppButton class="flex-1" :disabled="product.stock === 0" @click="addToCart">
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
