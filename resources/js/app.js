import { createInertiaApp } from '@inertiajs/vue3'
import { createApp, h } from 'vue'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { reveal } from './composables/useReveal'
import StorefrontLayout from './layouts/StorefrontLayout.vue'
import AnalyticsConsent from './components/storefront/AnalyticsConsent.vue'

createInertiaApp({
  // Judul sudah dikirim lengkap dari server lewat prop seoHead (lihat PageSeo),
  // dan setiap halaman memakainya lewat <Head :title="seoHead.title" />. Jadi
  // callback ini cukup meneruskan apa adanya.
  //
  // Callback lama menambahkan "— Arafagift" di sini. Itu tepat untuk respons
  // pertama, tapi ruang brand juga sudah dipotong server, dan setiap <Head>
  // akan menimpa title itu dengan headline mentah begitu Vue mount. Hasilnya
  // crawler yang merender JS akan membaca judul yang berbeda dari HTML awal.
  // Sekarang satu sumber kebenaran: PageSeo.
  title: (title) => title,
  resolve: async (name) => {
    const page = await resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue'))
    if (page.default.layout === undefined) page.default.layout = StorefrontLayout
    return page
  },
  setup({ el, App, props, plugin }) {
    // Banner consent hanya untuk storefront/checkout. Halaman admin tidak
    // memuat analytics sama sekali.
    const isAdminPage = String(props.initialPage?.component ?? '').startsWith('admin/')

    createApp({
      render: () => [
        h(App, props),
        isAdminPage ? null : h(AnalyticsConsent),
      ],
    })
      .use(plugin)
      .directive('reveal', reveal)
      .mount(el)
  },
})
