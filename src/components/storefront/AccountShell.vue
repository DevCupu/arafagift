<script setup>
import { Heart, LogOut, MapPin, Package, User } from 'lucide-vue-next'
import { useWishlist } from '@/composables/useWishlist'

defineProps({ title: String, sub: String })
const wishlist = useWishlist()

const nav = [
  { label: 'Profil', to: '/akun', icon: User },
  { label: 'Pesanan saya', to: '/akun/pesanan', icon: Package },
  { label: 'Alamat', to: '/akun/alamat', icon: MapPin },
  { label: 'Wishlist', to: '/akun/wishlist', icon: Heart },
]
</script>

<template>
  <div class="shell grid gap-10 py-12 lg:grid-cols-[16rem_1fr] lg:gap-16 lg:py-16">
    <aside class="lg:sticky lg:top-28 lg:h-fit">
      <div class="flex items-center gap-4 border border-line bg-surface px-5 py-4">
        <span class="arch grid h-12 w-9 place-items-end border border-line bg-ivory pb-1.5">
          <span class="h-1.5 w-1.5 rounded-full bg-gold" />
        </span>
        <div>
          <p class="font-display text-[1.15rem] leading-none text-forest">Ratna Halim</p>
          <p class="mt-1.5 text-[0.72rem] text-muted">Anggota sejak Maret 2025</p>
        </div>
      </div>
      <nav class="mt-4 border border-line bg-surface">
        <ul class="divide-y divide-line">
          <li v-for="item in nav" :key="item.to">
            <router-link
              :to="item.to"
              class="flex items-center gap-3 px-5 py-3.5 text-[0.85rem] text-muted transition hover:bg-ivory hover:text-forest"
              active-class="text-forest"
              :exact="item.to === '/akun'"
            >
              <component :is="item.icon" class="h-4 w-4 text-gold" :stroke-width="1.5" />
              {{ item.label }}
              <span v-if="item.to === '/akun/wishlist' && wishlist.count.value" class="ml-auto text-[0.72rem] text-muted">
                {{ wishlist.count.value }}
              </span>
            </router-link>
          </li>
          <li>
            <button class="flex w-full items-center gap-3 px-5 py-3.5 text-[0.85rem] text-muted transition hover:bg-ivory hover:text-forest">
              <LogOut class="h-4 w-4 text-gold" :stroke-width="1.5" /> Keluar
            </button>
          </li>
        </ul>
      </nav>
    </aside>

    <section>
      <header class="border-b border-line pb-6">
        <h1 class="text-[2.1rem] leading-none sm:text-[2.5rem]">{{ title }}</h1>
        <p v-if="sub" class="mt-3 text-[0.88rem] text-muted">{{ sub }}</p>
      </header>
      <div class="pt-8"><slot /></div>
    </section>
  </div>
</template>
