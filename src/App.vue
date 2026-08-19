<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import StorefrontLayout from '@/layouts/StorefrontLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import BareLayout from '@/layouts/BareLayout.vue'
import ToastHost from '@/components/ui/ToastHost.vue'

const route = useRoute()
const layouts = { admin: AdminLayout, bare: BareLayout }
const layout = computed(() => layouts[route.meta.layout] ?? StorefrontLayout)
</script>

<template>
  <component :is="layout">
    <router-view v-slot="{ Component }">
      <Transition
        mode="out-in"
        enter-active-class="transition duration-300 ease-calm"
        enter-from-class="opacity-0 translate-y-1"
        leave-active-class="transition duration-150"
        leave-to-class="opacity-0"
      >
        <component :is="Component" />
      </Transition>
    </router-view>
  </component>
  <ToastHost />
</template>
