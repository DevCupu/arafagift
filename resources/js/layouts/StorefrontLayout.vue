<script setup>
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AnnouncementBar from '@/components/storefront/AnnouncementBar.vue'
import SiteNavbar from '@/components/storefront/SiteNavbar.vue'
import SiteFooter from '@/components/storefront/SiteFooter.vue'
import CartDrawer from '@/components/storefront/CartDrawer.vue'
import ToastHost from '@/components/ui/ToastHost.vue'

const page = usePage()

const organizationJsonLd = computed(() => {
  const store = page.props.store ?? {}
  return {
    '@context': 'https://schema.org',
    '@type': 'LocalBusiness',
    name: store.name || 'ArafahGift.id',
    description: 'Toko oleh-oleh dan hadiah haji & umrah: kurma, sajadah, tasbih, kalung, sarung, dan gift set.',
    address: store.address ? { '@type': 'PostalAddress', streetAddress: store.address, addressLocality: store.originCity } : undefined,
    email: store.email || undefined,
    telephone: store.whatsapp || undefined,
  }
})
</script>

<template>
  <Head>
    <component :is="'script'" type="application/ld+json">{{ JSON.stringify(organizationJsonLd) }}</component>
  </Head>
  <div class="flex min-h-screen flex-col bg-ivory">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[80] focus:bg-forest focus:px-4 focus:py-2 focus:text-ivory">
      Lompat ke konten
    </a>
    <AnnouncementBar :message="page.props.announcement" />
    <SiteNavbar />
    <main id="konten" class="flex-1"><slot /></main>
    <SiteFooter />
    <CartDrawer />
    <ToastHost />
  </div>
</template>
