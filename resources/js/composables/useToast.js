import { ref } from 'vue'

const toasts = ref([])
let seq = 0

export function useToast() {
  const dismiss = (id) => { toasts.value = toasts.value.filter((t) => t.id !== id) }
  const push = (message, { tone = 'default', timeout = 3600 } = {}) => {
    const id = ++seq
    toasts.value = [...toasts.value, { id, message, tone }]
    setTimeout(() => dismiss(id), timeout)
    return id
  }
  return { toasts, push, dismiss }
}
