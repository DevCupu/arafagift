<script setup>
import AnnouncementBar from '@/components/storefront/AnnouncementBar.vue'
import SiteNavbar from '@/components/storefront/SiteNavbar.vue'
import SiteFooter from '@/components/storefront/SiteFooter.vue'
import CartDrawer from '@/components/storefront/CartDrawer.vue'
import { homeContent } from '@/data/content'
</script>

<template>
  <div class="flex min-h-screen flex-col bg-ivory">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[80] focus:bg-forest focus:px-4 focus:py-2 focus:text-ivory">
      Lompat ke konten
    </a>
    <AnnouncementBar :message="homeContent.announcement" />
    <SiteNavbar />
    <main id="konten" class="flex-1"><slot /></main>
    <SiteFooter />
    <CartDrawer />
  </div>
</template>
