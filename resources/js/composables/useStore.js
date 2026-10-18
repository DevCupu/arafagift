import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { buildWhatsAppHref } from './whatsapp'

export function useStore() {
  const page = usePage()
  const store = computed(() => page.props.store ?? {})

  const whatsappHref = (text) => buildWhatsAppHref(store.value.whatsapp, text)

  return { store, whatsappHref }
}
