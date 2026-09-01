import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useStore() {
  const page = usePage()
  const store = computed(() => page.props.store ?? {})

  const whatsappHref = (text) => {
    const number = String(store.value.whatsapp ?? '').replace(/\D/g, '')
    const qs = text ? `?text=${encodeURIComponent(text)}` : ''
    return `https://wa.me/${number}${qs}`
  }

  return { store, whatsappHref }
}