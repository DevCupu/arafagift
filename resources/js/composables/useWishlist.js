import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'

export function useWishlist() {
  const page = usePage()
  const { push } = useToast()

  const ids = computed(() => page.props.auth?.wishlistIds ?? [])
  const has = (id) => ids.value.includes(id)
  const count = computed(() => ids.value.length)

  const toggle = (id) => {
    if (!page.props.auth?.user) {
      push('Masuk dulu untuk menyimpan wishlist', { tone: 'default' })
      router.visit('/login')
      return
    }
    router.post(`/wishlist/${id}/toggle`, {}, { preserveScroll: true, preserveState: true })
  }

  return { ids, has, toggle, count }
}
