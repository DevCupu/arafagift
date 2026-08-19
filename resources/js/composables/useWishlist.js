import { computed, ref } from 'vue'
import { products } from '@/data/catalog'

const ids = ref([104, 108])

export function useWishlist() {
  const items = computed(() => products.filter((p) => ids.value.includes(p.id)))
  const has = (id) => ids.value.includes(id)
  const toggle = (id) => {
    ids.value = has(id) ? ids.value.filter((i) => i !== id) : [...ids.value, id]
    return has(id)
  }
  const remove = (id) => { ids.value = ids.value.filter((i) => i !== id) }
  return { ids, items, has, toggle, remove, count: computed(() => ids.value.length) }
}
