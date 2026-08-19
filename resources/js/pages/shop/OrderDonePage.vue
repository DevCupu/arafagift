<script setup>
import { Check } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import ProductArt from '@/components/art/ProductArt.vue'

defineProps({ order: { type: Object, required: true } })
</script>

<template>
  <div class="shell grid items-center gap-14 py-16 lg:grid-cols-2 lg:py-24">
    <div>
      <span class="inline-flex items-center gap-2 border border-gold/40 bg-gold/10 px-3 py-1.5 text-[0.68rem] uppercase tracking-[0.16em] text-[#7A5F26]">
        <Check class="h-3.5 w-3.5" /> Pesanan diterima
      </span>
      <h1 class="mt-7 text-[2.5rem] leading-[1.05] sm:text-[3.2rem]">Terima kasih. Pesanan Anda kami siapkan.</h1>
      <p class="mt-6 max-w-md text-[0.95rem] leading-relaxed text-muted">
        Nomor pesanan <span class="text-forest">{{ order.orderNumber }}</span> sudah dikirim ke email Anda beserta instruksi pembayaran.
        Kartu ucapan ditulis tangan sebelum box ditutup.
      </p>
      <dl class="mt-9 max-w-md divide-y divide-line border-y border-line text-[0.87rem]">
        <div class="flex justify-between py-3.5"><dt class="text-muted">Estimasi kemas</dt><dd class="text-forest">Hari ini, sebelum 17.00</dd></div>
        <div class="flex justify-between py-3.5"><dt class="text-muted">Estimasi tiba</dt><dd class="text-forest">{{ order.shippingEta }}</dd></div>
        <div class="flex justify-between py-3.5"><dt class="text-muted">Metode bayar</dt><dd class="text-forest">{{ order.paymentMethodName }}</dd></div>
      </dl>
      <div class="mt-9 flex flex-wrap gap-3">
        <AppButton to="/akun/pesanan" size="lg">Lacak pesanan</AppButton>
        <AppButton to="/koleksi" variant="outline" size="lg">Lanjut belanja</AppButton>
      </div>
    </div>
    <div class="arch arch--deep overflow-hidden border border-line bg-surface">
      <div class="aspect-[5/6]"><ProductArt art="giftset" :tone="1" /></div>
    </div>
  </div>
</template>
