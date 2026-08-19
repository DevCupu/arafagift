import { createInertiaApp } from '@inertiajs/vue3'
import { createApp, h } from 'vue'
import { MotionPlugin } from 'motion-v'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { reveal } from './composables/useReveal'
import StorefrontLayout from './layouts/StorefrontLayout.vue'

createInertiaApp({
  title: (title) => (title ? `${title} — ArafahGift.id` : 'ArafahGift.id — Oleh-oleh Umrah & Hajj yang dipilih dengan hati'),
  resolve: async (name) => {
    const page = await resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue'))
    page.default.layout ??= StorefrontLayout
    return page
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(MotionPlugin)
      .directive('reveal', reveal)
      .mount(el)
  },
})
