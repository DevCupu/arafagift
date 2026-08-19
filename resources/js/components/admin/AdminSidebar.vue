<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import {
  BarChart3, Boxes, FileText, LayoutDashboard, Package, Settings,
  ShoppingCart, Tag, Ticket, Users, X,
} from 'lucide-vue-next'
import BrandLogo from '@/components/storefront/BrandLogo.vue'

defineProps({ open: Boolean })
const emit = defineEmits(['close'])
const page = usePage()

const groups = [
  {
    title: 'Penjualan',
    items: [
      { label: 'Dashboard', to: '/admin', icon: LayoutDashboard, exact: true },
      { label: 'Pesanan', to: '/admin/pesanan', icon: ShoppingCart },
      { label: 'Pelanggan', to: '/admin/pelanggan', icon: Users },
    ],
  },
  {
    title: 'Katalog',
    items: [
      { label: 'Produk', to: '/admin/produk', icon: Package },
      { label: 'Kategori', to: '/admin/kategori', icon: Tag },
      { label: 'Inventori', to: '/admin/inventori', icon: Boxes },
      { label: 'Promo', to: '/admin/promo', icon: Ticket },
    ],
  },
  {
    title: 'Situs',
    items: [
      { label: 'Konten', to: '/admin/konten', icon: FileText },
      { label: 'Laporan', to: '/admin/laporan', icon: BarChart3 },
      { label: 'Pengaturan', to: '/admin/pengaturan', icon: Settings },
    ],
  },
]
</script>

<template>
  <aside
    class="fixed inset-y-0 left-0 z-[70] flex w-64 flex-col border-r border-forest-soft/30 bg-forest-deep transition-transform duration-300 ease-calm lg:translate-x-0"
    :class="open ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="flex h-16 items-center justify-between border-b border-ivory/10 px-5">
      <Link href="/"><BrandLogo tone="ivory" size="sm" /></Link>
      <button class="grid h-9 w-9 place-items-center text-ivory/60 lg:hidden" aria-label="Tutup menu admin" @click="emit('close')">
        <X class="h-4 w-4" />
      </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-6">
      <div v-for="g in groups" :key="g.title" class="mb-7">
        <p class="px-3 text-[0.62rem] uppercase tracking-[0.18em] text-gold/80">{{ g.title }}</p>
        <ul class="mt-3 space-y-0.5">
          <li v-for="item in g.items" :key="item.to">
            <Link
              :href="item.to"
              class="flex items-center gap-3 rounded px-3 py-2.5 text-[0.83rem] text-ivory/65 transition hover:bg-ivory/[0.07] hover:text-ivory"
              :class="page.url === item.to || (!item.exact && page.url.startsWith(item.to) && item.to !== '/admin')
                ? 'bg-ivory/[0.09] text-ivory' : ''"
              @click="emit('close')"
            >
              <component :is="item.icon" class="h-4 w-4" :stroke-width="1.5" />
              {{ item.label }}
            </Link>
          </li>
        </ul>
      </div>
    </nav>

    <div class="border-t border-ivory/10 px-5 py-4">
      <Link href="/" class="text-[0.75rem] text-ivory/50 transition hover:text-gold">← Lihat storefront</Link>
    </div>
  </aside>
</template>
