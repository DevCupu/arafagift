<script setup>
import AccountShell from '@/components/storefront/AccountShell.vue'
import ProductCard from '@/components/storefront/ProductCard.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import AppButton from '@/components/ui/AppButton.vue'

defineProps({ products: { type: Array, required: true } })
</script>

<template>
  <AccountShell title="Wishlist" sub="Yang Anda simpan untuk dibeli nanti.">
    <div v-if="products.length" class="grid grid-cols-2 gap-x-5 gap-y-12 lg:grid-cols-3 lg:gap-x-8">
      <ProductCard v-for="(p, i) in products" :key="p.id" :product="p" :index="i" />
    </div>
    <EmptyState v-else title="Wishlist masih kosong" body="Tekan ikon hati pada produk untuk menyimpannya di sini.">
      <AppButton to="/koleksi">Jelajahi koleksi</AppButton>
    </EmptyState>
  </AccountShell>
</template>
